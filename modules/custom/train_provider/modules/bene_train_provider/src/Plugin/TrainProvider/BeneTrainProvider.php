<?php

namespace Drupal\bene_train_provider\Plugin\TrainProvider;

use Drupal\bene_train_provider\BeneApi;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\train_base\Entity\Station;
use Drupal\bene_train_provider\CoachClassInfoHolder;
use Drupal\bene_train_provider\TrainInfoHolder;
use Drupal\train_provider\AvailableRoutesFormTrait;
use Drupal\train_provider\Entity\TrainProviderRequest;
use Drupal\train_provider\TrainProviderBase;
use Drupal\train_provider\AvailableBookingTrainProviderInterface;

/**
 * Provides Bene Train Provider.
 *
 * @TrainProvider(
 *   id = "bene_train_provider",
 *   label = "Bene train provider",
 *   description = "Gets timetable from BeNe and places bookings in their system",
 *   operations_provider = "Drupal\bene_train_provider\Plugin\TrainProvider\PluginOperationsProvider",
 *   price_updater = false
 * )
 */
class BeneTrainProvider extends TrainProviderBase implements AvailableBookingTrainProviderInterface {

  use DependencySerializationTrait, AvailableRoutesFormTrait;

  /**
   * @var string
   */
  protected static $supplierCode = 'BeNe';

  /**
   * Italian Trains train class code.
   *
   * @var string
   */
  protected static $trainClassCode = 'HS';

  /**
   * Italian Trains train class code.
   *
   * @var string
   */
  protected static $seatTypeCode = 'S';

  /**
   * @var \Drupal\bene_train_provider\BeneApi
   */
  protected $api;

  /**
   * The first leg can be skipped, if it does not satisfy the conditions (min_departure_window).
   *
   * @var boolean
   */
  protected $firstLegWasSkipped;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->api = new BeneApi($this->configuration);
  }

  public function defaultConfiguration() {
    return [
      'live' => 0,
      'log' => 0,
      'username' => '',
      'password' => '',
      'distributor' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form_state->setCached(FALSE);
    $form['#tree'] = TRUE;
    $form['live'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use live server'),
      '#default_value' => $this->isLive(),
    ];
    $form['log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log responses and requests to file'),
      '#default_value' => $this->configuration['log'],
    ];
    $this->getAvailableRoutesSettingsForm($form, $form_state);
    $form['max_nbr_connections'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of connections'),
      '#default_value' => $this->configuration['max_nbr_connections'],
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $this->configuration['username'],
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $this->configuration['password'],
    ];
    $form['distributor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Distributor'),
      '#default_value' => $this->configuration['distributor'],
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $this->configuration['email'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    // TODO: Implement validateConfigurationForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['live'] = (bool) $values['live'];
      $this->configuration['log'] = (bool) $values['log'];

      // Available routes.
      $availableRoutes = [];
      if (isset($form['routes_fieldset']['available_routes'])) {
        $availableRoutes = $form_state->getValue($form['routes_fieldset']['available_routes']['#parents']);
      }
      $this->configuration['available_routes'] = $availableRoutes;

      $this->configuration['max_nbr_connections'] = $values['max_nbr_connections'];
      $this->configuration['username'] = $values['username'];
      $this->configuration['password'] = $values['password'];
      $this->configuration['distributor'] = $values['distributor'];
      $this->configuration['email'] = $values['email'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeTable() {
    $trains = [1 => [], 2 => []];

    foreach ($this->configuration['legs'] as $leg => $legData) {

      // Don't make search if searching is earlier than today + min_departure_window.
      $min_departure_window = $this->getDaysNumberBeforeDeparture($this->getDepartureDate($leg));
      if ($min_departure_window < $this->getMinDaysBeforeDeparture() || $min_departure_window < $this->getCommonMinDaysBeforeDeparture()) {
        if ($leg == 1) {
          $this->firstLegWasSkipped = true;
        }
        continue;
      }

      // The route should be in the list of available routes.
      $availableRoutes = $this->getAvailableRoutes();
      if ($availableRoutes) {
        $isRouteAvailable = false;
        foreach ($this->getAvailableRoutes() as $availableRoute) {
          if (in_array($this->getDepartureStation($leg)->id(), $availableRoute) && in_array($this->getArrivalStation($leg)->id(), $availableRoute)) {
            $isRouteAvailable = true;
          }
        }
        if (!$isRouteAvailable) {
          continue;
        }
      }

      // @TODO: Seems like BeNe Train Provider can searching round trip if range between the dates is small.
      // BeNe Train Provider can searching round trip in the single request.
      if ($leg == 2 && $this->configuration['round_trip'] && !$this->firstLegWasSkipped) {
        continue;
      }

      // Stations must be enabled.
      if (!$this->getDepartureStation($leg)->isEnabled() || !$this->getArrivalStation($leg)->isEnabled()) {
        continue;
      }

      $stationsCodes = $this->prepareStations($leg);
      foreach ($stationsCodes['departure'] as $departureStationCode) {
        foreach ($stationsCodes['arrival'] as $arrivalStationCode) {

          // Searching should make only for stations, which contain station code for this provider.
          if (!$departureStationCode || !$arrivalStationCode) {
            continue;
          }

          // Initializes the point of the start day and the end.
          $departureTime = new DrupalDateTime($this->getDepartureDate($leg));
          $theEndOfDay = new DrupalDateTime($this->getDepartureDate($leg));
          $theEndOfDay->modify('+1 day');
          $returnDepartureTime = $returnTheEndOfDay = $returnIntervalDuration = null;

          // BeNe provides roundTrip option for single request. So we need do same to the return dates.
          if ($this->configuration['round_trip'] && !$this->firstLegWasSkipped) {
            $returnDepartureTime = new DrupalDateTime($this->getDepartureDate(2));
            $returnTheEndOfDay = new DrupalDateTime($this->getDepartureDate(2));
            $returnTheEndOfDay->modify('+1 day');
          }

          // According documentation we can retrieve only 5 trains from single request
          // without additional agreement with BeNe. So we should step by step increase departure time
          // and decrease interval duration, cause we need stay in the scope of current day.
          $makeSearch = $outwardMakeSearch = $returnMakeSearch = true;
          while($makeSearch) {
            // Getting interval duration.
            $diff = $theEndOfDay->diff($departureTime);
            $intervalDuration = $diff->days * 24 * 60;
            $intervalDuration += $diff->h * 60;
            $intervalDuration += $diff->i;

            // BeNe provides roundTrip option for single request. We have to get and keep
            // interval duration for inward trip separately.
            if ($this->configuration['round_trip'] && !$this->firstLegWasSkipped) {
              $returnDiff = $returnTheEndOfDay->diff($returnDepartureTime);
              $returnIntervalDuration = $returnDiff->days * 24 * 60;
              $returnIntervalDuration += $returnDiff->h * 60;
              $returnIntervalDuration += $returnDiff->i;
            }

            // Getting request parameters and call it.
            $params = $this->trainsAndProductsRequestParams($leg, $departureStationCode, $arrivalStationCode, $departureTime, $returnDepartureTime, $intervalDuration, $returnIntervalDuration);
            $result = $this->api->trainsAndProductsRequest($params);

            // @TODO: Find a way to get array as result from the api.
            $result = json_decode(json_encode($result), true);

            $this->updateTrainProviderRequest($this->getDepartureStation($leg), $this->getArrivalStation($leg), $this->getDepartureDate($leg), 'search');

            // The api can returns empty result without exception.
            if (empty($result['route-summary-list'])) {
              break;
            }

            // For single passenger need to wrap into array.
            if (isset($result['passenger-list-reply']['passenger-reply']['passenger-id'])) {
              $result['passenger-list-reply']['passenger-reply'] = [$result['passenger-list-reply']['passenger-reply']];
            }

            // Sometimes route summary can contains only one train.
            if (isset($result['route-summary-list']['route-summary']['route-summary-id'])) {
              $result['route-summary-list']['route-summary'] = [$result['route-summary-list']['route-summary']];
            }

            // Handle each train from result.
            $outwardTrains = $returnTrains = [];
            foreach ($result['route-summary-list']['route-summary'] as $routeSummary) {
              $originStation = $routeSummary['origin-station']['code'];
              $destinationStation = $routeSummary['destination-station']['code'];

              // BeNe provide
              if ($this->configuration['round_trip'] && !$this->isNeededRoute($originStation, $destinationStation, $leg)) {
                // BeNe provides roundTrip option for single request.
                // Search can stop for this leg, but can still going for another leg.
                // We can't stop all request, so we should just exclude adding to the trains.
                if ($returnMakeSearch) {
                  $trainInfoHolder = $this->convertTrainInfo($result['passenger-list-reply'], $routeSummary, $result['proposed-price-list'], $leg);
                  if ($trainInfoHolder) {
                    $trains[2][] = $trainInfoHolder;
                  }
                  $returnTrains[] = $routeSummary;
                }
              }
              else {
                // BeNe provides roundTrip option for single request.
                // Search can stop for this leg, but can still going for another leg.
                // We can't stop all request, so we should just exclude adding to the trains.
                if ($outwardMakeSearch) {
                  $trainInfoHolder = $this->convertTrainInfo($result['passenger-list-reply'], $routeSummary, $result['proposed-price-list'], $leg);
                  if ($trainInfoHolder) {
                    $trains[$leg][] = $trainInfoHolder;
                  }
                  $outwardTrains[] = $routeSummary;
                }
              }
            }

            // We should to get last train departure time. It will be departure time to the next request.
            // But we need increase the departure time on 13 minutes, otherwise we will get duplicate (the last train).
            // Why 13 minutes? Because with less minutes we got duplicate again.
            if ($outwardMakeSearch) {
              $this->sortTrainsByDepartureTime($outwardTrains);
              $lastTrain = end($outwardTrains);
              $departureDateString = $lastTrain['departure-date'] . ' ' . $lastTrain['departure-time'];
              $departureTime = new DrupalDateTime($departureDateString, $this->getDepartureStation($leg)->getTimezone());
              $departureTime->modify('+13 minutes');
              if ($departureTime->getTimestamp() >= $theEndOfDay->getTimestamp()) {
                // Search for outward leg has finished. But we must keep valid request.
                // So we need reset outward departure time. Departure time should stay in the scope of current day.
                $departureTime = new DrupalDateTime($this->getDepartureDate($leg));
                $outwardMakeSearch = false;
              }
            }
            // BeNe provides roundTrip option for single request. So we need do same to the inward leg.
            if ($this->configuration['round_trip'] && !$this->firstLegWasSkipped) {
              if ($returnMakeSearch) {
                $this->sortTrainsByDepartureTime($returnTrains);
                $returnLastTrain = end($returnTrains);
                $returnDateString = $returnLastTrain['departure-date'] . ' ' . $returnLastTrain['departure-time'];
                $returnDepartureTime = new DrupalDateTime($returnDateString, $this->getDepartureStation(2)->getTimezone());
                $returnDepartureTime->modify('+13 minutes');
                if ($returnDepartureTime->getTimestamp() >= $returnTheEndOfDay->getTimestamp()) {
                  // Search for inward leg has finished. But we must keep valid request.
                  // So we need reset inward departure time. Departure time should stay in the scope of current day.
                  $returnDepartureTime = new DrupalDateTime($this->getDepartureDate(2));
                  $returnMakeSearch = false;
                }
              }
            }

            // In this case we base on BeNe API logic. If we set interval duration,
            // which big enough for cover full current day (from current departure time to the end of day),
            // so if we retrieve count of trains less then we set (now 5), so that is all. Search has finished.
            if (count($outwardTrains) < $this->configuration['max_nbr_connections']) {
              $outwardMakeSearch = false;
            }
            // BeNe provides roundTrip option for single request. So we need do same to the inward leg.
            if ($this->configuration['round_trip'] && !$this->firstLegWasSkipped) {
              if (count($returnTrains) < $this->configuration['max_nbr_connections']) {
                $returnMakeSearch = false;
              }
            }

            // BeNe provides roundTrip option for single request. So we have tw o parameters for stopping searching.
            if ($this->configuration['round_trip'] && !$this->firstLegWasSkipped) {
              if (!$outwardMakeSearch && !$returnMakeSearch) {
                $makeSearch = false;
              }
            }
            else {
              if (!$outwardMakeSearch) {
                $makeSearch = false;
              }
            }
          }
        }
      }
    }

    return $trains;
  }

  /**
   * Gets TrainInfoHolder for this route.
   *
   * @param $passengerListReply
   * @param $routeSummary
   * @param $priceList
   * @param $leg
   * @return \Drupal\bene_train_provider\TrainInfoHolder|null
   */
  protected function convertTrainInfo($passengerListReply, $routeSummary, $priceList, $leg) {
    // Initialize TrainInfoHolder.
    $trainInfoHolder = new TrainInfoHolder();

    // Setting passenger data, will be use on the booking stage.
    $trainInfoHolder->setPassengerListReply($passengerListReply);

    // Sometimes proposed price can contains only one tariff. We have to sure, that
    // foreach will be execute correctly.
    if (!isset($priceList['proposed-price'][0])) {
      $priceList['proposed-price'][] = $priceList['proposed-price'];
    }

    $coachClassInfoHolders = [];
    foreach ($priceList['proposed-price'] as $price) {
      if ($price['route-summary-id'] == $routeSummary['route-summary-id']) {
        // Train can doesn't exist in local db, so need to set all possible values.
        if ($train = $this->getTrainByNumber($price['train-number'])) {
          $trainInfoHolder->setTrain($train);
        }
        else {
          $trainInfoHolder->setTrainNumber($price['train-number']);
          $supplier = $this->getSupplierByCode($price['product-feature']['carrier-description']['supplier']);
          $trainInfoHolder->setSupplier($supplier);
          $trainClass = $this->getTrainClass($supplier, static::$trainClassCode);
          $trainInfoHolder->setTrainClass($trainClass);
          $trainInfoHolder->setEticketAvailable(true);
        }

        // Setting departure and arrival stations received from API (not from search).
        $departureStation = $this->getStationByCode($price['origin-station']['code']);
        $arrivalStation = $this->getStationByCode($price['destination-station']['code']);
        if ($arrivalStation && $departureStation) {
          $trainInfoHolder->setDepartureStation($departureStation);
          $trainInfoHolder->setArrivalStation($arrivalStation);
        }
        else {
          return null;
        }

        // Setting train Departure time and Arrival time, also calculate manually Running time.
        // @todo: Received date without timezone. Need to check for clearing working.
        $departureDateString = $routeSummary['departure-date'] . ' ' . $routeSummary['departure-time'];
        $departureDatetime = new DrupalDateTime($departureDateString, $trainInfoHolder->getDepartureStation()->getTimezone());
        $arrivalDateString = $routeSummary['arrival-date'] . ' ' . $routeSummary['arrival-time'];
        $arrivalDatetime = new DrupalDateTime($arrivalDateString, $trainInfoHolder->getArrivalStation()->getTimezone());
        $trainInfoHolder->setRunningTime($arrivalDatetime->getTimestamp() - $departureDatetime->getTimestamp());
        $trainInfoHolder->setDepartureDateTime($departureDatetime);
        $trainInfoHolder->setArrivalDateTime($arrivalDatetime);
        $trainInfoHolder->setDepartureTime($departureDatetime->getTimestamp() - $this->getDepartureDate($leg)->getTimestamp());

        // Skip trains, which don't satisfied hours before departure conditions.
        $now = DrupalDateTime::createFromtimestamp(time(), $trainInfoHolder->getDepartureStation()->getTimezone());
        $now30 = DrupalDateTime::createFromtimestamp(time() + static::$minimalDepth * 60, $trainInfoHolder->getDepartureStation()->getTimezone());
        $diff = $departureDatetime->diff($now);
        $hoursBeforeDeparture = $diff->h;
        if (($now30->getTimestamp() >= $departureDatetime->getTimestamp()) ||
          ($diff->days == 0 && ($hoursBeforeDeparture < $this->getMinHoursBeforeDeparture() ||
              $hoursBeforeDeparture < $this->getCommonMinHoursBeforeDeparture()))) {
          return null;
        }

        // Setting coach classes for this train.
        $coachClassInfoHolder = $this->convertCoachClassInfo($price, $trainInfoHolder->getTrainNumber(), $trainInfoHolder->getSupplier(), $leg);
        if (!empty($coachClassInfoHolder)) {
          $coachClassInfoHolders = array_merge($coachClassInfoHolders, $coachClassInfoHolder);
        }
      }
    }
    // Skip trains without coach classes.
    if (empty($coachClassInfoHolders)) {
      return null;
    }
    $this->sortCoachClassInfoHolders($coachClassInfoHolders);
    $trainInfoHolder->setCoachClasses($coachClassInfoHolders);

    return $trainInfoHolder;
  }

  /**
   * Gets array of CoachClassInfoHolder
   *
   * @param $proposedPrice
   * @param string $trainNumber
   * @param \Drupal\train_base\Entity\Supplier $supplier
   * @param $leg
   * @return \Drupal\bene_train_provider\CoachClassInfoHolder[]
   */
  protected function convertCoachClassInfo($proposedPrice, $trainNumber, $supplier, $leg) {
    $priceRule = \Drupal::service('store.price_rule');
    $orderDepth = $this->getDaysNumberBeforeDeparture($this->configuration['legs'][$leg]['departure_date']);
    $currencyCode = \Drupal::service('store.default_currency')->getUserCurrency();

    // For single passenger need to wrap into array.
    if (isset($proposedPrice['price-offer']['used-tacos']['taco'])) {
      $proposedPrice['price-offer']['used-tacos'] = [$proposedPrice['price-offer']['used-tacos']];
    }

    $coachClassInfoHolders = [];
    foreach ($proposedPrice['price-offer']['used-tacos'] as $usedTaco) {

      // Skipp coachClasses which doesn't exist in the DB.
      if (!$coachClass = $this->getCoachClassByCodeAndSupplier($usedTaco['taco']['tic'], $supplier)) {
        continue;
      }

      // Amount can be empty (probably not available) - need to avoid those cases.
      if (empty($usedTaco['unit-price']['amount']) || $usedTaco['unit-price']['amount'] == -1) {
        continue;
      }

      /** @var \Drupal\store\Price $price */
      $price = \Drupal::service('store.price')->get($usedTaco['unit-price']['amount'], $usedTaco['unit-price']['currency']);
      if ($proposedPrice['segment-type'] == 'admission') {
        $price = $price->divide($usedTaco['number-of-passengers']);
      }
      $coachClassInfoHolder = new CoachClassInfoHolder();
      $coachClassInfoHolder->setProposedPrice($proposedPrice);
      $coachClassInfoHolder->setOriginalPrice($price);
      $coachClassInfoHolder->setCoachClass($coachClass);
      $coachClassInfoHolder->setPluginId($this->pluginId);
      $coachClassInfoHolder->setSeatType($this->getSeatType($supplier, static::$seatTypeCode));
      $coachClassInfoHolder->setCarServices($coachClass->getCarServices());

      // Switch price to user currency.*/
      $updatedPrice = $price->convert($currencyCode);

      // Implements before display price rules.
      $updatedPrice = $priceRule->updatePrice('before_display', $updatedPrice,
        ['train' => $trainNumber, 'supplier' => $supplier->getCode(), 'order_depth' => $orderDepth])['price'];
      $coachClassInfoHolder->setPrice($updatedPrice);
      $coachClassInfoHolders[] = $coachClassInfoHolder;
    }

    return $coachClassInfoHolders;
  }

  public function preBooking($legsResult, $order) {
    // TODO: Implement preBooking() method.
  }

  /**
   * @inheritdoc
   */
  public function finalizeBooking($legsResult, $order, $preBookingResponses) {
    $finalizeBookingResponse = $bookingData = $ticketRef = [];
    $dossierID = $status = $pinCode = null;
    $isProviderMonopolist = count($legsResult) == 2 ? true : false;

    // We need call request twice if it's complex trip type, or round trip.
    foreach ($legsResult as $leg => $legData) {
      if ($leg == 2 && $order->getTripType() == 'roundtrip' && $isProviderMonopolist) {
        break;
      }

      $params = $this->getBookingRequestParams($legsResult, $order, $leg);
      $response = $this->api->bookingRequest($params);
      // @TODO: Find a way to get array as result from the api.
      $finalizeBookingResponse[$leg] = json_decode(json_encode($response), TRUE);
    }

    foreach ($legsResult as $leg => $legData) {
      if ($leg == 2 && $order->getTripType() == 'roundtrip' && $isProviderMonopolist) {
        break;
      }
      /** @var \Drupal\train_base\Entity\Station $departureStation */
      $departureStation = $this->configuration['legs'][$leg]['departure_station'];
      /** @var \Drupal\train_base\Entity\Station $arrivalStation */
      $arrivalStation = $this->configuration['legs'][$leg]['arrival_station'];
      $route = $departureStation->getName() . 'To' . $arrivalStation->getName();

      if ($finalizeBookingResponse[$leg] && !empty($finalizeBookingResponse[$leg]['dossier-id'])) {
        $status = 'booked';
        // @TODO: PNR can does not exist (need to investigate what will be a booking key).
        //$bookingKey = $response['pnr'];
        $pinCode = $finalizeBookingResponse[$leg]['pin-code'];
        $dossierID = $finalizeBookingResponse[$leg]['dossier-id'];
        $response = $this->retrieveDossierRequest($dossierID);
        if ($response) {
          // Get cancellation offer request.
          $ticketRef = [];
          foreach ($response['ticket-list'] as $tickets) {
            foreach ($tickets['ticket-info'] as $ticket) {
              if ($ticket['ticket-price']['amount'] > 0) {
                $ticket['ticket-ref']['status'] = $status;
                $ticketRef[$ticket['ticket-ref']['booking-id']] = $ticket['ticket-ref'];
              }
            }
          }
        }
        $this->updateTrainProviderRequest($this->getDepartureStation($leg), $this->getArrivalStation($leg), $this->getDepartureDate($leg), 'success_booking');
      }
      else {
        $this->updateTrainProviderRequest($this->getDepartureStation($leg), $this->getArrivalStation($leg), $this->getDepartureDate($leg), 'failed_booking');
      }
      $bookingData = $order->getData('bookingData');
      $bookingData[$leg] = [
        'providerId' => $this->pluginId,
        'route' => $route,
        'bookingKey' => $dossierID,
        'pinCode' => $pinCode,
        'dossierID' => $dossierID,
        'status' => $status,
        'ticketRef' => $ticketRef,
      ];

      // Saving booking data on the order.
      $order->setData('bookingData', $bookingData);
      $order->save();
    }

    return $finalizeBookingResponse;
  }

  /**
   * @param array $bookingData
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $cancelLeg
   * @return array
   */
  public function cancelBooking($bookingData, $order, $cancelLeg) {
    $response = null;

    $dossierID = $bookingData[$cancelLeg]['dossierID'];
    $retrieveDossierRequestResponse = $this->retrieveDossierRequest($dossierID);

    if ($retrieveDossierRequestResponse) {
      // We should take only not canceled tickets with positive price.
      $ticketRef = [];
      foreach ($retrieveDossierRequestResponse['ticket-list'] as $tickets) {
        foreach ($tickets['ticket-info'] as $ticket) {
          if ($ticket['cancel-state-info'] == 'N') {
            if ($ticket['ticket-price']['amount'] > 0) {
              $ticketRef[] = $ticket['ticket-ref'];
            }
          }
        }
      }

      // Get response from getCancellationOfferRequest.
      $getCancellationOfferRequestResponse = $this->getCancellationOfferRequest($dossierID, $ticketRef);
      if (!$getCancellationOfferRequestResponse) {
        $message = $this->t('Something going wrong, probably you have non refundable ticket in the order. Please, contact with developers.');
        drupal_set_message($message);
        return $response;
      }

      // Prepare parameters for execute tickets cancellation.
      $params = [
        'execute-cancellation-request' => [
          'user-data' => $this->getUserData(),
          'dossier-id' => $bookingData[$cancelLeg]['dossierID'],
        ],
      ];
      foreach ($ticketRef as $ticket) {
        foreach ($getCancellationOfferRequestResponse['cancellation-conditions']['cancellation-condition'] as $cancellationCondition) {
          if ($cancellationCondition['booking-id'] == $ticket['booking-id']) {
            $ticket['maximum-carrier-fee'] = $cancellationCondition['ticket-amounts']['carrier-fee'];
          }
        }
        $params['execute-cancellation-request']['ticket-ref-cancellation'][] = $ticket;
      }

      // Execute tickets cancellation.
      $response = $this->api->executeCancellationRequest($params);
      // @TODO: Find a way to get array as result from the api.
      $response = json_decode(json_encode($response), true);

      // Update booking data.
      if (isset($response['cancel-OK']) && $response['cancel-OK']) {
        $bookingData = $order->getData('bookingData');
        $bookingData[$cancelLeg]['status'] = 'canceled';
        foreach ($bookingData[$cancelLeg]['ticketRef'] as &$ticket) {
          $ticket['status'] = 'canceled';
        }
        $order->setData('bookingData', $bookingData)->save();
        drupal_set_message(t('Tickets for this leg was canceled successfully'));
      }
    }

    return $response;
  }

  /**
   * @param array $bookingData
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $cancelLeg
   * @param $ticketId
   * @return array
   */
  public function cancelTicketBooking($bookingData, $order, $cancelLeg, $ticketId) {
    $response = null;

    // Prepare booking parameters.
    $dossierID = $bookingData[$cancelLeg]['dossierID'];
    $ticketRef = $bookingData[$cancelLeg]['ticketRef'][$ticketId];
    unset($ticketRef['status']);

    // Get response from getCancellationOfferRequest.
    $getCancellationOfferRequestResponse = $this->getCancellationOfferRequest($dossierID, $ticketRef);
    if (!$getCancellationOfferRequestResponse) {
      $message = $this->t('Something going wrong, probably you have non refundable ticket in the order. Please, contact with developers.');
      drupal_set_message($message);
      return $response;
    }

    // Prepare parameters for execute tickets cancellation.
    $params = [
      'execute-cancellation-request' => [
        'user-data' => $this->getUserData(),
        'dossier-id' => $bookingData[$cancelLeg]['dossierID'],
      ],
    ];
    foreach ($getCancellationOfferRequestResponse['cancellation-conditions']['cancellation-condition'] as $cancellationCondition) {
      if ($cancellationCondition['booking-id'] == $ticketRef['booking-id']) {
        $ticketRef['maximum-carrier-fee'] = $cancellationCondition['ticket-amounts']['carrier-fee'];
      }
    }
    $params['execute-cancellation-request']['ticket-ref-cancellation'] = $ticketRef;

    // Execute tickets cancellation.
    $response = $this->api->executeCancellationRequest($params);
    // @TODO: Find a way to get array as result from the api.
    $response = json_decode(json_encode($response), true);

    // Update booking data.
    if (isset($response['cancel-OK']) && $response['cancel-OK']) {
      $bookingData = $order->getData('bookingData');
      $bookingData[$cancelLeg]['ticketRef'][$ticketId]['status'] = 'canceled';
      $hasBookedTickets = false;
      foreach ($bookingData[$cancelLeg]['ticketRef'] as $ticketId => $ticket) {
        if ($ticket['status'] == 'booked') {
          $hasBookedTickets = true;
        }
      }
      if (!$hasBookedTickets) {
        $bookingData[$cancelLeg]['status'] = 'canceled';
      }
      $order->setData('bookingData', $bookingData)->save();
      drupal_set_message(t('Tickets for this leg was canceled successfully'));
    }
  }

  /**
   * @param array $bookingData
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $infoLeg
   * @return null|array
   */
  public function getInfo($bookingData, $order, $infoLeg) {
    $response = $this->retrieveDossierRequest($bookingData[$infoLeg]['dossierID']);
    if ($response) {
      // We should take only not canceled tickets with positive price.
      $ticketRef = [];
      foreach ($response['ticket-list'] as $tickets) {
        foreach ($tickets['ticket-info'] as $ticket) {
          if ($ticket['cancel-state-info'] == 'N') {
            if ($ticket['ticket-price']['amount'] > 0) {
              $ticketRef[] = $ticket['ticket-ref'];
            }
          }
        }
      }

      // Get response from getCancellationOfferRequest.
      $getCancellationOfferRequestResponse = $this->getCancellationOfferRequest($bookingData[$infoLeg]['dossierID'], $ticketRef);

      // Form getInfo message.
      $output = '<div>';
      $counter = 0;
      foreach ($response['ticket-list'] as $tickets) {
        foreach ($tickets['ticket-info'] as $ticket) {
          // Skipp seat reservation tickets.
          if ($ticket['ticket-price']['amount'] <= 0) {
            continue;
          }

          $status = $ticket['booking-state-info'] == 'T' && $ticket['cancel-state-info'] == 'N' ? 'Booked' : 'Canceled';
          $output .= '<div>BookingStatus: ' . $status . '</div>';
          if ($status == 'Booked') {
            $price = \Drupal::service('store.price')
              ->get($ticket['ticket-price']['amount'], $ticket['ticket-price']['currency']);
            $output .= '<div>TicketPrice: ' . $price . '</div>';
            if ($getCancellationOfferRequestResponse) {
              foreach ($getCancellationOfferRequestResponse['cancellation-conditions']['cancellation-condition'] as $cancellationCondition) {
                if ($cancellationCondition['booking-id'] == $ticket['ticket-ref']['booking-id']) {
                  $amount = $cancellationCondition['ticket-amounts']['carrier-fee'];
                  $currency = $ticket['ticket-price']['currency'];
                  $cancelPrice = \Drupal::service('store.price')
                    ->get($amount, $currency);
                  $output .= '<div>CancelCost: ' . $cancelPrice . '</div>';
                }
              }
            }
            else {
              $output .= '<div>CancelCost: Can\'t be canceled</div>';
            }
            $counter++;
          }
          else {
            $price = \Drupal::service('store.price')
              ->get($ticket['cancel-cost']['amount'], $ticket['cancel-cost']['currency']);
            $output .= '<div>CancelCost: ' . $price . '</div>';
          }
        }
      }
      $output .= '</div>';

      // @TODO: Move to twig template when html structure will be close to finalize version.
      $message['info'] = [
        '#markup' => $output,
      ];
      drupal_set_message($message);
    }

    return $response;
  }

  /**
   * @param array $bookingData
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $pickedLeg
   * @return array|null
   */
  public function checkPdf($bookingData, $order, $pickedLeg) {
    $response = null;
    if (!empty($bookingData[$pickedLeg]['bookingKey'])) {
      $response = $this->manageFulfillmentRequest($bookingData[$pickedLeg]['dossierID'], $bookingData[$pickedLeg]['pinCode']);
      if (!empty($response['HP-url'])) {
        $bookingData = $order->getData('bookingData');
        $bookingData[$pickedLeg]['pdf'] = $response['HP-url'];
        $order->setData('bookingData', $bookingData)->save();
      }
    }

    return $response;
  }

  /**
   * @param $dossierID
   * @return array|null
   */
  protected function retrieveDossierRequest($dossierID) {
    $params = [
      'retrieve-dossier-request' => [
        'user-data' => $this->getUserData(),
        'dossier-id' => $dossierID,
      ],
    ];
    $response = $this->api->retrieveDossierRequest($params);
    // @TODO: Find a way to get array as result from the api.
    $response = json_decode(json_encode($response), true);

    // For single ticket need to wrap into array.
    if (isset($response['ticket-list']['ticket-info'])) {
      $response['ticket-list'] = [$response['ticket-list']];
    }

    foreach ($response['ticket-list'] as &$tickets) {
      // For single ticket need to wrap into array.
      if (isset($tickets['ticket-info']['ticket-id'])) {
        $tickets['ticket-info'] = [$tickets['ticket-info']];
      }
    }

    return $response;
  }

  /**
   * @param $dossierID
   * @param $ticketRef
   * @return mixed|null
   */
  protected function getCancellationOfferRequest($dossierID, $ticketRef) {
    $params = [
      'get-cancellation-offer-request' => [
        'user-data' => $this->getUserData(),
        'dossier-id' => $dossierID,
        'ticket-ref' => $ticketRef,
      ],
    ];
    $response = $this->api->getCancellationOfferRequest($params);
    // @TODO: Find a way to get array as result from the api.
    $response = json_decode(json_encode($response), true);

    // For single ticket need to wrap into array.
    if (isset($response['cancellation-conditions']['cancellation-condition']['booking-id'])) {
      $response['cancellation-conditions']['cancellation-condition'] = [$response['cancellation-conditions']['cancellation-condition']];
    }

    return $response;
  }

  /**
   * Get PDF for current booking.
   *
   * @param $dossierID
   * @param $pinCode
   * @return null|array
   */
  protected function manageFulfillmentRequest($dossierID, $pinCode) {
    $params = $this->getManageFulfillmentRequestParams($dossierID, 'PDF_DT', $pinCode, false);
    $response = $this->api->manageFulfillmentRequest($params);
    // @TODO: Find a way to get array as result from the api.
    $response = json_decode(json_encode($response), true);

    return $response;
  }

  /**
   * Get RouteData (train number, fare details) for picked route.
   *
   * @return string
   */
  public function getRouteData() {
    $html = [];

    foreach ($this->configuration['legs'] as $leg => $legData) {

      // Don't make search if searching is earlier than today + min_departure_window.
      $min_departure_window = $this->getDaysNumberBeforeDeparture($this->getDepartureDate($leg));
      if ($min_departure_window < $this->getMinDaysBeforeDeparture() || $min_departure_window < $this->getCommonMinDaysBeforeDeparture()) {
        if ($leg == 1) {
          $this->firstLegWasSkipped = true;
        }
        continue;
      }

      // The route should be in the list of available routes.
      $isRouteAvailable = false;
      foreach ($this->getAvailableRoutes() as $availableRoute) {
        if (in_array($this->getDepartureStation($leg)->id(), $availableRoute) && in_array($this->getArrivalStation($leg)->id(), $availableRoute)) {
          $isRouteAvailable = true;
        }
      }
      if (!$isRouteAvailable) {
        continue;
      }

      $stationsCodes = $this->prepareStations($leg);
      foreach ($stationsCodes['departure'] as $departureStationCode) {
        foreach ($stationsCodes['arrival'] as $arrivalStationCode) {

          // Searching should make only for stations, which contain station code for this provider.
          if (!$departureStationCode || !$arrivalStationCode) {
            continue;
          }

          // Initializes the point of the start day and the end.
          $departureTime = new DrupalDateTime($this->getDepartureDate($leg));
          $theEndOfDay = new DrupalDateTime($this->getDepartureDate($leg));
          $theEndOfDay->modify('+1 day');
          $returnDepartureTime = $returnTheEndOfDay = $returnIntervalDuration = null;

          // According documentation we can retrieve only 5 trains from single request
          // without additional agreement with BeNe. So we should step by step increase departure time
          // and decrease interval duration, cause we need stay in the scope of current day.
          $makeSearch = $outwardMakeSearch = $returnMakeSearch = true;
          while($makeSearch) {
            // Getting interval duration.
            $diff = $theEndOfDay->diff($departureTime);
            $intervalDuration = $diff->days * 24 * 60;
            $intervalDuration += $diff->h * 60;
            $intervalDuration += $diff->i;

            // Getting request parameters and call it.
            $params = $this->trainsAndProductsRequestParams($leg, $departureStationCode, $arrivalStationCode, $departureTime, $returnDepartureTime, $intervalDuration, $returnIntervalDuration);
            $result = $this->api->trainsAndProductsRequest($params);

            // @TODO: Find a way to get array as result from the api.
            $result = json_decode(json_encode($result), true);

            $this->updateTrainProviderRequest($this->getDepartureStation($leg), $this->getArrivalStation($leg), $this->getDepartureDate($leg), 'search');

            // The api can returns empty result without exception.
            if (empty($result['route-summary-list'])) {
              break;
            }

            // For single passenger need to wrap into array.
            if (isset($result['passenger-list-reply']['passenger-reply']['passenger-id'])) {
              $result['passenger-list-reply']['passenger-reply'] = [$result['passenger-list-reply']['passenger-reply']];
            }

            // Sometimes route summary can contains only one train.
            if (isset($result['route-summary-list']['route-summary']['route-summary-id'])) {
              $result['route-summary-list']['route-summary'] = [$result['route-summary-list']['route-summary']];
            }

            // Handle each train from result.
            $outwardTrains = [];
            foreach ($result['route-summary-list']['route-summary'] as $routeSummary) {
              $trainData = $this->getTrainData($routeSummary, $result['proposed-price-list'], $leg);
              $html[] = $this->renderTrainData($trainData);
              $outwardTrains[] = $routeSummary;
            }

            // We should to get last train departure time. It will be departure time to the next request.
            // But we need increase the departure time on 13 minutes, otherwise we will get duplicate (the last train).
            // Why 13 minutes? Because with less minutes we got duplicate again.
            if ($outwardMakeSearch) {
              $this->sortTrainsByDepartureTime($outwardTrains);
              $lastTrain = end($outwardTrains);
              $departureDateString = $lastTrain['departure-date'] . ' ' . $lastTrain['departure-time'];
              $departureTime = new DrupalDateTime($departureDateString, $this->getDepartureStation($leg)->getTimezone());
              $departureTime->modify('+13 minutes');
              if ($departureTime->getTimestamp() >= $theEndOfDay->getTimestamp()) {
                // Search for outward leg has finished. But we must keep valid request.
                // So we need reset outward departure time. Departure time should stay in the scope of current day.
                $departureTime = new DrupalDateTime($this->getDepartureDate($leg));
                $outwardMakeSearch = false;
              }
            }

            // In this case we base on BeNe API logic. If we set interval duration,
            // which big enough for cover full current day (from current departure time to the end of day),
            // so if we retrieve count of trains less then we set (now 5), so that is all. Search has finished.
            if (count($outwardTrains) < $this->configuration['max_nbr_connections']) {
              $outwardMakeSearch = false;
            }

            if (!$outwardMakeSearch) {
              $makeSearch = false;
            }
          }
        }
      }
    }

    $result = '';
    foreach ($html as $output) {
      $result .= $output;
    }

    return $result;
  }

  /**
   * @param $routeSummary
   * @param $priceList
   * @param $leg
   * @return array
   */
  protected function getTrainData($routeSummary, $priceList, $leg) {
    $trainData = [];

    // Sometimes proposed price can contains only one tariff. We have to sure, that
    // foreach will be execute correctly.
    if (!isset($priceList['proposed-price'][0])) {
      $priceList['proposed-price'][] = $priceList['proposed-price'];
    }

    foreach ($priceList['proposed-price'] as $price) {
      if ($price['route-summary-id'] == $routeSummary['route-summary-id']) {
        $trainNumber = $price['train-number'];
        $supplierCode = $price['product-feature']['carrier-description']['supplier'];
        $supplierName = $price['product-feature']['carrier-description']['_'];

        // Setting departure and arrival stations received from API (not from search).
        $departureStation=  $this->getStationByCode($price['origin-station']['code']);
        $arrivalStation = $this->getStationByCode($price['destination-station']['code']);

        // Setting train Departure time and Arrival time, also calculate manually Running time.
        // @todo: Received date without timezone. Need to check for clearing working.
        $departureDateString = $routeSummary['departure-date'] . ' ' . $routeSummary['departure-time'];
        $departureDatetime = new DrupalDateTime($departureDateString, $this->getDepartureStation($leg)->getTimezone());
        $arrivalDateString = $routeSummary['arrival-date'] . ' ' . $routeSummary['arrival-time'];
        $arrivalDatetime = new DrupalDateTime($arrivalDateString, $this->getArrivalStation($leg)->getTimezone());

        // For single passenger need to wrap into array.
        if (isset($price['price-offer']['used-tacos']['taco'])) {
          $price['price-offer']['used-tacos'] = [$price['price-offer']['used-tacos']];
        }

        $usedTacos = [];
        foreach ($price['price-offer']['used-tacos'] as $usedTaco) {
          // Amount can be empty (probably not available) - need to avoid those cases.
          if (empty($usedTaco['unit-price']['amount']) || $usedTaco['unit-price']['amount'] == -1) {
            continue;
          }

          /** @var \Drupal\store\Price $price */
          $unitPrice = \Drupal::service('store.price')
            ->get($usedTaco['unit-price']['amount'], $usedTaco['unit-price']['currency']);
          $usedTaco['taco']['unitPrice'] = $unitPrice;
          $usedTacos[] = $usedTaco['taco'];
        }

        unset($price['product-feature']['carrier-description']);
        $trainData[] = [
          'trainNumber' => $trainNumber,
          'supplierCode' => $supplierCode,
          'supplierName' => $supplierName,
          'departureDatetime' => $departureDatetime->format(DATETIME_DATETIME_STORAGE_FORMAT),
          'arrivalDatetime' => $arrivalDatetime->format(DATETIME_DATETIME_STORAGE_FORMAT),
          'departureStation' => $departureStation->getName(),
          'arrivalStation' => $arrivalStation->getName(),
          'usedTacos' =>  $usedTacos,
          'productFeature' => $price['product-feature'],
          'segmentType' => $price['segment-type'],
          'classOfService' => $price['availability']['class-of-service'],
          'availabilityLevel' => $price['availability']['availability-level'],
        ];
      }
    }

    return $trainData;
  }

  /**
   * @param array $trainData
   * @return string
   */
  protected function renderTrainData($trainData) {
    // @TODO: Do we need move it to twig template?
    $output = '<div>';
    $output .= '<div><span>Train number: ' . $trainData[0]['trainNumber'] . '</span></div>';
    $output .= '___________________________________________</br></br>';
    foreach ($trainData as $coachClassData) {
      foreach ($coachClassData as $key => $value) {
        if ($key == 'usedTacos') {
          foreach ($value as  $usedTacos) {
            foreach ($usedTacos as $tacoKey => $usedTaco) {
              $output .= '<div>' . $tacoKey . ': ' . $usedTaco . '</div>';
            }
          }
        }
        else if ($key == 'productFeature')  {
          foreach ($value as $innerKey => $innerValue) {
            if ($innerKey == 'EBS' || $innerKey == 'ESN') {
              if ($innerValue) {
                $output .= '<div>' . $innerKey . ': true</div>';
              }
              else {
                $output .= '<div>' . $innerKey . ': false</div>';
              }
            }
            else {
              $output .= '<div>' . $innerKey . ': ' . $innerValue . '</div>';
            }
          }
        }
        else {
          $output .= '<div>' . $key . ': ' .  $value . '</div>';
        }
      }
      $output .= '</br></br>';
    }
    $output .= '</div>';

    return $output;
  }

  /**
   * @param $leg
   * @param $departureStationCode
   * @param $arrivalStationCode
   * @param $departureTime
   * @param $returnDepartureTime
   * @param $intervalDuration
   * @param $returnIntervalDuration
   * @return array
   */
  protected function trainsAndProductsRequestParams($leg, $departureStationCode, $arrivalStationCode, $departureTime, $returnDepartureTime, $intervalDuration, $returnIntervalDuration) {
    $departureDate = $this->getDepartureDate($leg);
    $params = [
      'trains-and-product-request' => [
        'user-data' => $this->getUserData(),
        'journey-query' => [
          'origin-station' => ['code' => $departureStationCode],
          'destination-station' => ['code' => $arrivalStationCode],
          'outward-route-query' => [
            'date' => $departureDate->format('Y-m-d'),
            'time' => $departureTime->format('H:i:s'),//'10:00:00',//$departureTime->format('H:i:s'),
            'is-departure' => true,
            'interval-duration' => $intervalDuration,
            'max-nbr-connections' => $this->configuration['max_nbr_connections'],
          ],
          'number-of-transfers' => 0,
          'passenger-list-request' => [],
        ],
        'user-profile' => [
          'travel-prefs' => [],
          'response-prefs' => [],
        ],
      ],
    ];

    // @TODO:  When a return trip was requested, it is important to know that in the proposed-price-list
    // price elements will be offered for the outward and the inward leg separately, but not all of them can be combined.
    // In order to know which prices can be combined, these combinations are given in the reply in the element outward-return-price list.
    // As this takes some extra processing, this list must be requested. By default it is not generated.
    if ($this->configuration['round_trip'] && !$this->firstLegWasSkipped) {
      $params['trains-and-product-request']['journey-query']['return-route-query']['date'] = $this->getDepartureDate(2)->format('Y-m-d');
      $params['trains-and-product-request']['journey-query']['return-route-query']['time'] = $returnDepartureTime->format('H:i:s');//'16:00:00';//$returnDepartureTime->format('H:i:s');
      $params['trains-and-product-request']['journey-query']['return-route-query']['is-departure'] = true;
      $params['trains-and-product-request']['journey-query']['return-route-query']['interval-duration'] = $returnIntervalDuration;
      $params['trains-and-product-request']['journey-query']['return-route-query']['max-nbr-connections'] = $this->configuration['max_nbr_connections'];
    }

    $email =  !empty($this->configuration['email']) ? 'smtp://' . $this->configuration['email'] : '';
    for ($i = 0; $i < $this->configuration['adult_number']; $i++) {
      $params['trains-and-product-request']['journey-query']['passenger-list-request']['passenger-request'][] = [
        'passenger-type' => 'A',
        'external-pass-ref' => $email,
      ];

    }
    for ($i = 0; $i < $this->configuration['child_number']; $i++) {
      $params['trains-and-product-request']['journey-query']['passenger-list-request']['passenger-request'][] = [
        'passenger-type' => 'A',
        'external-pass-ref' => $email,
      ];
    }

    return $params;
  }

  /**
   * @param $legsResult
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $leg
   * @return array
   */
  protected function getBookingRequestParams($legsResult, $order, $leg) {
    $passengerData = $this->getPassengerData($legsResult, $order, $leg);
    $proposedPrice = $this->getProposedPrice($legsResult, $order, $leg);
    $params = [
      'booking-request' => [
        'user-data' => $this->getUserData(),
        'booking-type' => 'ticket',
        'booking-info' => [
          'delivery-info' => [
            'delivery-method' => ['code' => 'DH', '_' => 'PDF_DT'],
          ],
          'ticket-language' => ['code' => 'en_GB', '_' => 'United Kingdom'],
        ],
        'passenger-list-reply' => [
            'passenger-reply' => $passengerData,
        ],
        'booking-request-list' => [
          'proposed-price' => $proposedPrice,
        ],
      ],
    ];

    return $params;
  }

  /**
   * @param $dossierID
   * @param $printerID
   * @param $pinCode
   * @param $sync
   * @return array
   */
  protected function getManageFulfillmentRequestParams($dossierID, $printerID, $pinCode, $sync) {
    return [
      'manage-fulfillment-request' => [
        'user-data' => $this->getUserData(),
        'dossier-id' => $dossierID,
        'fulfillment-mode' => $printerID,
        'pin-code' => $pinCode,
      ],
    ];
  }

  /**
   * @return array
   */
  protected function getUserData() {
    return [
      'user-name' => $this->configuration['username'],
      'password' => $this->configuration['password'],
      'language' => ['code' => 'en_GB', '_' => 'United Kingdom'],
      'distributor' => $this->configuration['distributor'],
      'user-related-data' => 'rail.ninja',
    ];
  }

  /**
   * @param $legsResult
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $leg
   * @return array
   */
  protected function getPassengerData($legsResult, $order, $leg) {
    /** @var \Drupal\bene_train_provider\TrainInfoHolder $trainInfoHolder */
    $trainInfoHolder = $legsResult[$leg]['train_info_holder'];
    $passengerListReply = $trainInfoHolder->getPassengerListReply();
    $adultNumber = (int) $this->configuration['adult_number'];
    $counter = 0;
    $passengerData = [];
    $tickets = $order->getTickets();
    /** @var \Drupal\train_base\Entity\TrainTicket $ticket */
    foreach ($tickets as $ticket) {
      // BeNe doesn't support roundTrip for different passengers.
      // So passengers data for second leg is same.
      if ($ticket->getLegNumber() == $leg) {
        $passengers = $ticket->getPassengers();
        /** @var \Drupal\train_base\Entity\Passenger $passenger */
        foreach ($passengers as $passenger) {
          $counter++;
          $paxType = $counter <= $adultNumber ? 'A' : 'C';

          // @TODO: Used for night trains only.
          switch ($passenger->getGender()) {
            case 'male':
              $genderCode = 'H';
              $genderLabel = 'Mr.';
              break;
            case 'female':
              $genderCode = 'D';
              $genderLabel = 'Mrs.';
              break;
            default:
              $genderCode = 'H';
              $genderLabel = 'Mr.';
              break;
          }
          $passengerData[] = [
            'first-name' => $passenger->getFirstName(),
            'last-name' => $passenger->getLastName(),
            'gender' => ['code' => $genderCode, '_' => $genderLabel],
            // @TODO: Now we can booking only adults.
            'passenger-type' => 'A',
            'passenger-id' => $passengerListReply['passenger-reply'][$counter-1]['passenger-id'],
            'external-pass-ref' => $passengerListReply['passenger-reply'][$counter-1]['external-pass-ref'],
          ];
        }
      }
    }

    return $passengerData;
  }

  /**
   * @param $legsResult
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $leg
   * @return array
   */
  protected function getProposedPrice($legsResult, $order, $leg) {
    $proposedPrice = [];

    foreach ($legsResult as $innerLeg => $legResult) {
      if ($order->getTripType() == 'complex' && $innerLeg == 2 && $leg == 1) {
        break;
      }
      if ($order->getTripType() == 'complex' && $innerLeg == 1 && $leg == 2) {
        continue;
      }
      /** @var \Drupal\bene_train_provider\CoachClassInfoHolder $coachClassInfoHolder */
      $coachClassInfoHolder = $legResult['coach_class_info_holder'];
      $proposedPrice[] = $coachClassInfoHolder->getProposedPrice();
    }

    return $proposedPrice;
  }

  /**
   * Bene returns return routes for roundtrip with outward routes. Needs to filter it, also needs to consider that station can has a children.
   *
   * @param string $originStationCode
   * @param string $destinationStationCode
   * @param $leg
   * @return bool
   */
  protected function isNeededRoute($originStationCode, $destinationStationCode, $leg) {
    $departureStationCodes = $arrivalStationCodes = [];
    $departureStations = $this->entityTypeManager->getStorage('station')->loadMultiple($this->getStationChildren($this->getDepartureStation($leg)));
    $departureStations[] = $this->getDepartureStation($leg);
    /** @var \Drupal\train_base\Entity\Station $departureStation */
    foreach ($departureStations as $departureStation) {
      $departureStationCodes[] = $departureStation->getStationCodeBySupplierCode(static::$supplierCode);
    }
    $arrivalStations = $this->entityTypeManager->getStorage('station')->loadMultiple($this->getStationChildren($this->getArrivalStation($leg)));
    $arrivalStations[] = $this->getArrivalStation($leg);
    /** @var \Drupal\train_base\Entity\Station $arrivalStation */
    foreach ($arrivalStations as $arrivalStation) {
      $arrivalStationCodes[] = $arrivalStation->getStationCodeBySupplierCode(static::$supplierCode);
    }
    if (in_array($originStationCode, $departureStationCodes) && in_array($destinationStationCode, $arrivalStationCodes)) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * Sort Trains by its departure time.
   *
   * @param array $trains
   */
  protected function sortTrainsByDepartureTime(&$trains) {
    usort($trains, array($this, 'cmpSortTrainsByDepartureTime'));
  }

  /**
   * @param $train1
   * @param $train2
   * @return int
   */
  private static function cmpSortTrainsByDepartureTime($train1, $train2) {
    $departureTimeString1 = $train1['departure-date'] . ' ' . $train1['departure-time'];
    $departureTime1 = new DrupalDateTime($departureTimeString1);
    $departureTimeString2 = $train2['departure-date'] . ' ' . $train2['departure-time'];
    $departureTime2 = new DrupalDateTime($departureTimeString2);
    if ($departureTime1->getTimestamp() == $departureTime2->getTimestamp()) {
      return 0;
    }
    else if ($departureTime1->getTimestamp() < $departureTime2->getTimestamp()) {
      return -1;
    }
    else {
      return 1;
    }
  }

  /**
   * Updates success search statistic.
   *
   * @param \Drupal\train_base\Entity\Station $departureStation
   * @param \Drupal\train_base\Entity\Station $arrivalStation
   * @param \Drupal\Core\Datetime\DrupalDateTime $departureDate
   */
  protected function updateTrainProviderRequest(Station $departureStation, Station $arrivalStation, DrupalDateTime $departureDate, $op = 'search') {
    try {
      $departure_station_timezone = $departureDate->getTimezone();
      $today = DrupalDateTime::createFromtimestamp(time(), ['timezone' => $departure_station_timezone]);
      $today->setTime(0, 0);
      $depth = $departureDate->diff($today)->days;
      $today = DrupalDateTime::createFromtimestamp(time());
      $trainProviderRequest = null;

      $entity_ids = \Drupal::entityQuery('train_provider_request')
        ->condition('departure_station', $departureStation->id())
        ->condition('arrival_station', $arrivalStation->id())
        ->condition('date_of_search', $today->format(DATETIME_DATE_STORAGE_FORMAT))
        ->condition('provider_id', $this->pluginId)
        ->condition('depth', $depth)
        ->execute();
      if (!empty($entity_ids)) {
        /** @var \Drupal\train_booking\Entity\SuccessSearchDetailed $trainProviderRequest */
        $trainProviderRequest = $this->entityTypeManager->getStorage('train_provider_request')->load(reset($entity_ids));
        switch ($op) {
          case 'search':
            $trainProviderRequest->incrementCount();
            break;
          case 'success_booking':
            $trainProviderRequest->incrementSuccessBookingCount();
            break;
          case 'failed_booking':
            $trainProviderRequest->incrementFailedBookingCount();
            break;
        }
      }
      else {
        $trainProviderRequest = TrainProviderRequest::create([
          'departure_station' => $departureStation,
          'arrival_station' => $arrivalStation,
          'date_of_search' => $today->format(DATETIME_DATE_STORAGE_FORMAT),
          'provider_id' => $this->pluginId,
          'depth' => $depth ,
        ]);
        switch ($op) {
          case 'search':
            $trainProviderRequest->incrementCount();
            break;
          case 'success_booking':
            $trainProviderRequest->incrementSuccessBookingCount();
            break;
          case 'failed_booking':
            $trainProviderRequest->incrementFailedBookingCount();
            break;
        }
      }
      $trainProviderRequest->save();
    }
    catch (\Exception $e) {
      // Avoid from any errors because of stat
    }
  }
}