<?php

namespace Drupal\it_train_provider\Plugin\TrainProvider;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\it_train_provider\ItApi;
use Drupal\train_provider\AvailableBookingTrainProviderInterface;
use Drupal\train_provider\AvailableRoutesFormTrait;
use Drupal\train_provider\TrainProviderBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\it_train_provider\TrainInfoHolder;
use Drupal\it_train_provider\CoachClassInfoHolder;

/**
 * Provides It Train Provider.
 *
 * @TrainProvider(
 *   id = "it_train_provider",
 *   label = "It train provider",
 *   description = "Italo treno integration.",
 *   operations_provider = "Drupal\it_train_provider\Plugin\TrainProvider\ItPluginOperationsProvider",
 *   price_updater = true
 * )
 */
class ItTrainProvider extends TrainProviderBase implements AvailableBookingTrainProviderInterface {

  use DependencySerializationTrait, AvailableRoutesFormTrait;

  /**
   * Italian Trains supplier code.
   *
   * @var string
   */
  protected static $supplierCode = 'IT';

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
   * @var \Drupal\it_train_provider\ItApi
   */
  protected $api;

  /**
   * The first leg can be skipped, if it does not satisfy the conditions (min_departure_window).
   *
   * @var boolean
   */
  protected $firstLegWasSkipped;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->api = new ItApi($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form_state->setCached(false);
    $form['live'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Live'),
      '#default_value' => $this->isLive(),
    ];
    $form['log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log'),
      '#default_value' => $this->configuration['log'],
    ];
    $form['log_on_exception'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log on exception'),
      '#default_value' => $this->configuration['log_on_exception'],
    ];
    $this->getAvailableRoutesSettingsForm($form, $form_state);
    $this->addPaymentMethods($form, $form_state);
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
    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#default_value' => $this->configuration['domain'],
    ];
    $form['source_system'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source system'),
      '#default_value' => $this->configuration['source_system'],
    ];
    $form['id_partner'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID partner'),
      '#default_value' => $this->configuration['id_partner'],
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Contact details for IT'),
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
      $this->configuration['log_on_exception'] = (bool) $values['log_on_exception'];

      // Available routes.
      $availableRoutes = [];
      if (isset($form['routes_fieldset']['available_routes'])) {
        $availableRoutes = $form_state->getValue($form['routes_fieldset']['available_routes']['#parents']);
      }
      $this->configuration['available_routes'] = $availableRoutes;

      // Payment methods.
      $this->configuration['payment_methods'] = $values['payment_methods'];

      $this->configuration['username'] = $values['username'];
      $this->configuration['password'] = $values['password'];
      $this->configuration['domain'] = $values['domain'];
      $this->configuration['source_system'] = $values['source_system'];
      $this->configuration['id_partner'] = $values['id_partner'];
      $this->configuration['email'] = $values['email'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeTable() {
    $trains = [1 => [], 2 => []];
    $roundTripStartDateTime = $roundTripEndDateTime = null;

    // IT provide info about all classes only for 3h time period.
    // So we make 8 requests and aggregate their.
    // For special class, as example "LowestFareClass", we can get full day timetable.
    foreach ($this->configuration['legs'] as $outerLeg => $legData) {
      $orderDepth = $this->getDaysNumberBeforeDeparture($this->getDepartureDate($outerLeg));

      // Escape from searching big order depth requests.
      if ($this->getMaxDaysBeforeDeparture() != -1 && $orderDepth > $this->getMaxDaysBeforeDeparture()) {
        continue;
      }

      // Don't make search if searching is earlier than today + order depth.
      if ($orderDepth < $this->getMinDaysBeforeDeparture() || $orderDepth < $this->getCommonMinDaysBeforeDeparture()) {
        if ($outerLeg == 1) {
          $this->firstLegWasSkipped = true;
        }
        continue;
      }

      // The route should be in the list of available routes.
      $availableRoutes = $this->getAvailableRoutes();
      if ($availableRoutes) {
        $isRouteAvailable = false;
        foreach ($this->getAvailableRoutes() as $availableRoute) {
          if (in_array($this->getDepartureStation($outerLeg)->id(), $availableRoute) && in_array($this->getArrivalStation($outerLeg)->id(), $availableRoute)) {
            $isRouteAvailable = true;
          }
        }
        if (!$isRouteAvailable) {
          continue;
        }
      }

      // It Train Provider can searching round trip in the single request.
      if ($outerLeg == 2 && $this->configuration['round_trip'] && !$this->firstLegWasSkipped) {
        continue;
      }

      // Stations must be enabled.
      if (!$this->getDepartureStation($outerLeg)->isEnabled() || !$this->getArrivalStation($outerLeg)->isEnabled()) {
        continue;
      }

      $stationsCodes = $this->prepareStations($outerLeg);
      foreach ($stationsCodes['departure'] as $departureStationCode) {
        foreach ($stationsCodes['arrival'] as $arrivalStationCode) {

          // Searching should make only for stations, which contain station code for this provider.
          if (!$departureStationCode || !$arrivalStationCode) {
            continue;
          }

          $departureDate = $this->getDepartureDate($outerLeg);
          $params = $this->getGetAvailableTrainsParams($departureStationCode, $arrivalStationCode);
          $startDateTime = DrupalDateTime::createFromTimestamp($departureDate->getTimestamp(), $departureDate->getTimezone());
          $endDateTime = DrupalDateTime::createFromTimestamp($departureDate->getTimestamp(), $departureDate->getTimezone());
          $endDateTime->modify('-1 second');
          if ($this->configuration['round_trip'] && !$this->firstLegWasSkipped) {
            $returnDate = $this->getDepartureDate(2);
            $roundTripStartDateTime = DrupalDateTime::createFromTimestamp($returnDate->getTimestamp(), $returnDate->getTimezone());
            $roundTripEndDateTime = DrupalDateTime::createFromTimestamp($returnDate->getTimestamp(), $returnDate->getTimezone());
            $roundTripEndDateTime->modify('-1 second');
          }
          $nowTime = time();
          for ($i = 0; $i <= 8; $i++) {
            $journeyDateMarketData = [];
            $endDateTime->modify('+3 hours');
            if ($this->configuration['round_trip'] && !$this->firstLegWasSkipped) {
              $roundTripEndDateTime->modify('+3 hours');
            }
            if ($nowTime <= $endDateTime->getTimestamp() || $this->configuration['round_trip']) {
              $params['GetAvailableTrainsRequest']['GetAvailableTrains']['IntervalStartDateTime'] = $startDateTime->format(DATETIME_DATETIME_STORAGE_FORMAT);
              $params['GetAvailableTrainsRequest']['GetAvailableTrains']['IntervalEndDateTime'] = $endDateTime->format(DATETIME_DATETIME_STORAGE_FORMAT);
              if ($this->configuration['round_trip'] && !$this->firstLegWasSkipped) {
                $params['GetAvailableTrainsRequest']['GetAvailableTrains']['RoundTripIntervalStartDateTime'] = $roundTripStartDateTime->format(DATETIME_DATETIME_STORAGE_FORMAT);
                $params['GetAvailableTrainsRequest']['GetAvailableTrains']['RoundTripIntervalEndDateTime'] = $roundTripEndDateTime->format(DATETIME_DATETIME_STORAGE_FORMAT);
              }
              $response = $this->api->getAvailableTrains($params);
              if ($response && !empty($journeyDateMarkets = $response->JourneyDateMarkets)) {
                // JourneyDateMarket can be array if it's roundTrip.
                if (!is_array($journeyDateMarkets->JourneyDateMarket)) {
                  $journeyDateMarketData[] = $journeyDateMarkets->JourneyDateMarket;
                  $journeyDateMarkets->JourneyDateMarket = $journeyDateMarketData;
                }
                foreach ($journeyDateMarkets->JourneyDateMarket as $innerLeg => $journeyDateMarket) {
                  $innerLeg++; // Numeration of legs should starts from 1.
                  $journeyData = [];
                  if (!empty($journeys = $journeyDateMarket->Journeys)) {
                    // Journey is not array if journey is alone. We need make it universal.
                    if (!is_array($journeys->Journey)) {
                      $journeyData[] = $journeys->Journey;
                      $journeys->Journey = $journeyData;
                    }
                    foreach ($journeys->Journey as $journey) {
                      if (count($journey->Segments) == 1) {
                        // If it's a complex trip but not round trip should use our numeration of legs.
                        // In other cases should use numeration of legs from provider.
                        if (($this->configuration['complex_trip'] && !$this->configuration['round_trip']) ||
                          ($this->configuration['round_trip'] && $this->firstLegWasSkipped)
                        ) {
                          $leg = $outerLeg;
                        }
                        else {
                          $leg = $innerLeg;
                        }
                        $trainInfoHolder = $this->convertTrainInfo($journey, $response->Signature, $leg);
                        if ($trainInfoHolder) {
                          $trains[$leg][] = $trainInfoHolder;
                        }
                      }
                    }
                  }
                }
              }
            }
            $startDateTime->modify('+3 hours');
            if ($this->configuration['round_trip'] && !$this->firstLegWasSkipped) {
              $roundTripStartDateTime->modify('+3 hours');
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
   * @param $journey
   * @param string $signature
   * @param $leg
   * @return \Drupal\train_provider\TrainInfoHolder|null
   */
  protected function convertTrainInfo($journey, $signature, $leg) {
    // Initialize TrainInfoHolder.
    $trainInfoHolder = new TrainInfoHolder();

    // Setting the unique identifier of the journey.
    $trainInfoHolder->setSignature($signature);
    $trainInfoHolder->setJourneySellKey($journey->JourneySellKey);

    $segment = $journey->Segments->Segment;

    // Train can doesn't exist in local db, so need to set all possible values.
    if ($train = $this->getTrainByNumber($segment->TrainNumber)) {
      $trainInfoHolder->setTrain($train);
    }
    else {
      $trainInfoHolder->setTrainNumber($segment->TrainNumber);
      $supplier = $this->getSupplierByCode(static::$supplierCode);
      $trainInfoHolder->setSupplier($supplier);
      $trainInfoHolder->setTrainClass($this->getTrainClass($supplier, static::$trainClassCode));
      $trainInfoHolder->setEticketAvailable(true);
    }

    // Setting departure and arrival stations received from API (not from search).
    if (is_array($segment->Legs->Leg)) {
      $departureStation = $this->getStationByCode(reset($segment->Legs->Leg)->DepartureStation);
      $arrivalStation = $this->getStationByCode(end($segment->Legs->Leg)->ArrivalStation);
    }
    else {
      $departureStation = $this->getStationByCode($segment->Legs->Leg->DepartureStation);
      $arrivalStation = $this->getStationByCode($segment->Legs->Leg->ArrivalStation);
    }
    if ($arrivalStation && $departureStation) {
      $trainInfoHolder->setDepartureStation($departureStation);
      $trainInfoHolder->setArrivalStation($arrivalStation);
    }
    else {
      return null;
    }

    // Setting train Departure time and Arrival time, also calculate manually Running time.
    // @todo: Received date in the Italian time. Need to check for clearing working.
    $departureDatetime = DrupalDateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $segment->STD, $trainInfoHolder->getDepartureStation()->getTimezone());
    $arrival_datetime = DrupalDateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $segment->STA, $trainInfoHolder->getArrivalStation()->getTimezone());
    $trainInfoHolder->setRunningTime($arrival_datetime->getTimestamp() - $departureDatetime->getTimestamp());
    $trainInfoHolder->setDepartureDateTime($departureDatetime);
    $trainInfoHolder->setArrivalDateTime($arrival_datetime);
    $trainInfoHolder->setDepartureTime($departureDatetime->getTimestamp() - $this->configuration['legs'][$leg]['departure_date']->getTimestamp());

    // Set ticket issue date.
    if ($leg == 1) {
      $ticketIssueDate = DrupalDateTime::createFromTimestamp(strtotime(date('Y-m-d')));
      $trainInfoHolder->setTicketIssueDate($ticketIssueDate);
    }

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
    if (!empty($segment->Fares)) {
      $coachClasses = $this->convertCoachClassInfo($segment->Fares, $trainInfoHolder->getTrainNumber(), $trainInfoHolder->getSupplier(), $leg);
      // Skip trains without coach classes.
      if (empty($coachClasses)) {
        return null;
      }
      $trainInfoHolder->setCoachClasses($coachClasses);
    }
    else {
      return null;
    }

    return $trainInfoHolder;
  }

  /**
   * Gets array of CoachClassInfoHolder fo this train.
   *
   * @param $fares
   * @param string $trainNumber
   * @param \Drupal\train_base\Entity\Supplier $supplier
   * @param $leg
   * @return \Drupal\it_train_provider\CoachClassInfoHolder[]
   */
  protected function convertCoachClassInfo($fares, $trainNumber, $supplier, $leg) {
    $coachClassInfoHolders = [];
    $price_rule = \Drupal::service('store.price_rule');
    $order_depth = $this->getDaysNumberBeforeDeparture($this->configuration['legs'][$leg]['departure_date']);
    $currency_code = \Drupal::service('store.default_currency')->getUserCurrency();
    if (!is_array($fares->Fare)) {
      $fareData[] = $fares->Fare;
      $fares->Fare = $fareData;
    }
    foreach ($fares->Fare as $fare) {
      $coachClass = null;
      $paxFares = $fare->PaxFares;
      if (!$coachClass = $this->getCoachClassByCodeAndSupplier($fare->ProductClass . $fare->ClassOfService, $supplier)) {
        continue;
      }
      if (!is_array($paxFares->PaxFare)) {
        $paxFareData[] = $paxFares->PaxFare;
        $paxFares->PaxFare = $paxFareData;
      }
      foreach ($paxFares->PaxFare as $paxFare) {
        $prices[$paxFare->PaxType] = [
          'FullPaxFarePrice' => $paxFare->FullPaxFarePrice,
          'DiscountedPaxFarePrice' => $paxFare->DiscountedPaxFarePrice,
        ];
      }
      if (!empty($prices)) {
        /** @var \Drupal\store\Price $price */
        $price = \Drupal::service('store.price')->get($prices['ADT']['FullPaxFarePrice'], 'EUR');
        $coachClassInfoHolder = new CoachClassInfoHolder();
        $coachClassInfoHolder->setOriginalPrice($price);
        $coachClassInfoHolder->setCoachClass($coachClass);
        $coachClassInfoHolder->setPluginId($this->pluginId);
        $coachClassInfoHolder->setFareSellKey($fare->FareSellKey);
        $coachClassInfoHolder->setSeatType($this->getSeatType($supplier, static::$seatTypeCode));
        $coachClassInfoHolder->setCarServices($coachClass->getCarServices());

        // Switch price to user currency.
        $updated_price = $price->convert($currency_code);

        // Implements before display price rules.
        $updated_price = $price_rule->updatePrice('before_display', $updated_price,
          ['train' => $trainNumber, 'supplier' => $supplier->getCode(), 'order_depth' => $order_depth])['price'];
        $coachClassInfoHolder->setPrice($updated_price);
        $coachClassInfoHolders[] = $coachClassInfoHolder;
      }
    }
    $this->sortCoachClassInfoHolders($coachClassInfoHolders);

    return $coachClassInfoHolders;
  }

  /**
   * @inheritdoc
   */
  public function preBooking($legsResult, $order) {
    $response = [];
    $order->setData('preBooking1', 'prebooking started');
    $isProviderMonopolist = count($legsResult) == 2 ? true : false;
    // We need call request twice if it's complex trip type, one for other cases.
    foreach ($legsResult as $leg => $legResult) {
      /** @var \Drupal\it_train_provider\TrainInfoHolder $trainInfoHolder */
      $trainInfoHolder = $legResult['train_info_holder'];
      if ($leg == 2 && $order->getTripType() == 'roundtrip' && $isProviderMonopolist) {
        break;
      }
      $order->setData('holdBooking' . $leg, 'holdBooking started');
      $params = $this->getHoldBookingParams($trainInfoHolder->getSignature(), $legsResult, $order, $leg);
      $response[$leg] = $this->api->holdBooking($params);
      $managePaymentParams = $this->getManagePaymentParams($trainInfoHolder->getSignature());
      $order->setData('managePayment' . $leg, 'managePayment started');
      $this->managePayment($managePaymentParams);
    }
    $order->setData('preBooking2', 'prebooking completed')->save();

    return $response;
  }

  /**
   * @inheritdoc
   */
  public function finalizeBooking($legsResult, $order, $preBookingResponses) {
    $finalizeBookingResponse = $bookingData = $itPDF = $totalPassengerData = [];
    $response = null;
    $isProviderMonopolist = count($legsResult) == 2 ? true : false;

    $order->setData('finalizeBooking1', 'finalize booking started')->save();

    // We need call request twice if it's complex trip type, one for other cases.
    foreach ($preBookingResponses as $leg => $preBookingResponse) {
      $passengerTempData = [];
      $passengerData = $preBookingResponse->Booking->Passengers->Passenger;
      $order->setData('doFinalizeBooking' . $leg, 'doFinalizeBooking started')->save();
      $response = $this->doFinalizeBooking($preBookingResponse->Signature, $order, $passengerData);
      $finalizeBookingResponse[$leg] = $response;
      // PassengerData is not array if it's alone. We need make it universal.
      if (!is_array($passengerData)) {
        $passengerTempData[] = $passengerData;
        $passengerData = $passengerTempData;
      }
      $totalPassengerData[$leg] = $passengerData;
    }

    $order->setData('finalizeBooking2', 'completed')->save();

    $bookingKey = $signature = $status = $firstName = $lastName = null;
    foreach ($legsResult as $leg => $legData) {
      if ($leg == 2 && $order->getTripType() == 'roundtrip' && $isProviderMonopolist) {
        break;
      }
      /** @var \Drupal\train_base\Entity\Station $departureStation */
      $departureStation = $this->getDepartureStation($leg);
      /** @var \Drupal\train_base\Entity\Station $arrivalStation */
      $arrivalStation = $this->getArrivalStation($leg);
      $route = $departureStation->getName() . 'To' . $arrivalStation->getName();
      if (!empty($finalizeBookingResponse[$leg])) {
        $bookingKey = $finalizeBookingResponse[$leg]->BookingSummary->PNR;
        $signature = $finalizeBookingResponse[$leg]->Signature;
        $status = 'booked';
        $firstName = $totalPassengerData[$leg][0]->FirstName;
        $lastName = $totalPassengerData[$leg][0]->LastName;
        $message = '';
      }
      else {
        $message = $this->t('The order didn\'t book. Please, contact with IT department.');
      }
      // @todo set statuses about booking and payment
      $bookingData = $order->getData('bookingData');
      $bookingData[$leg] = [
        'providerId' => $this->pluginId,
        'route' => $route,
        'bookingKey' => $bookingKey,
        'signature' => $signature,
        'status' => $status,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'message' => $message,
      ];
      // Saving booking data on the order.
      $order->setData('bookingData', $bookingData)->save();
    }
    $order->setData('finalizeBooking3', 'absolutely completed')->save();

    return $finalizeBookingResponse;
  }

  /**
   * @inheritdoc
   */
  public function cancelBooking($bookingData, $order, $cancelLeg) {
    $response = [];
    $paymentID = null;

    $bookingDetailResponse = $this->getBooking($bookingData, $order, $cancelLeg);
    if ($bookingDetailResponse) {
      $signature = $bookingDetailResponse->Signature;
      // Payment is not array if it's alone. We need make it universal.
      $payments = $bookingDetailResponse->Booking->Payments;
      if (!is_array($payments->Payment)) {
        $paymentData[] = $payments->Payment;
        $payments->Payment = $paymentData;
      }
      foreach ($payments->Payment as $payment) {
        // @TODO: Payments can be array - idk about PaymentID, seems it can be different. Need to detect true PaymentID.
        if ($payment->BookingPaymentStatus == 'Approved') {
          $paymentID = $payment->PaymentID;
        }
      }
    }
    else {
      return $response;
    }

    // Payment was not completed, so can't be canceled.
    // @TODO: Or can?
    if (!$paymentID || $paymentID == 0) {
      return $response;
    }

    $deleteJourneyResponse = $this->deleteJourney($signature, $bookingDetailResponse->Booking->Journeys->Journey);
    if (!$deleteJourneyResponse) {
      return $response;
    }
    drupal_set_message(t('Ticket for this leg was canceled successfully'));

    $params = $this->getCancelBookingParams($signature, $paymentID);
    $managePaymentResponse = $this->managePayment($params);
    if (!$managePaymentResponse) {
      return $response;
    }

    $response = $this->doFinalizeBooking($signature, $order, $bookingDetailResponse->Booking->Passengers->Passenger);
    if ($response) {
      $bookingData = $order->getData('bookingData');
      $bookingData[$cancelLeg]['status'] = 'canceled';
      $order->setData('bookingData', $bookingData)->save();
    }

    return [$cancelLeg => $response];
  }

  /**
   * @param array $bookingData
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $infoLeg
   * @return null|\stdClass
   */
  public function getInfo($bookingData, $order, $infoLeg) {
    $response = $this->getBooking($bookingData, $order, $infoLeg);
    if ($response) {
      $output = '<div>';
      $output .= '<div>BookingStatus: ' . $response->Booking->BookingStatus . '</div>';
      $output .= '<div>TotalCost: ' . $response->Booking->BookingSum->TotalCost . '</div>';
      $output .= '</div>';
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
   * @return array|void
   */
  public function checkPdf($bookingData, $order, $pickedLeg) {
    $bookingDetailResponse = $this->getBooking($bookingData, $order, $pickedLeg);
    if ($bookingDetailResponse && $bookingDetailResponse->Booking->BookingStatus != 'HoldCanceled') {
      $response = $this->getPDFTicket($bookingDetailResponse->Signature);
      if ($response) {
        $bookingData = $order->getData('bookingData');
        $bookingData[$pickedLeg]['pdf'] = $response->PdfUrl;
        $order->setData('bookingData', $bookingData)->save();
      }
    }
  }

  /**
   * Call ManagePayment function.
   *
   * @param array $params
   * @return null|\stdClass
   */
  protected function managePayment($params) {
    $response = $this->api->managePayment($params);

    return $response;
  }

  /**
   * @param $signature
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $passengerData
   * @return null|\stdClass
   */
  protected function doFinalizeBooking($signature, $order, $passengerData) {
    $params = $this->getFinalizeBookingParams($signature, $order, $passengerData);
    $response = $this->api->finalizeBooking($params);

    return $response;
  }

  /**
   * @param $bookingData
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $pickedLeg
   * @return null|\stdClass
   */
  protected function getBooking($bookingData, $order, $pickedLeg) {
    $params = $this->getGetBookingParams($bookingData, $order, $pickedLeg);
    $response = $this->api->getBooking($params);

    return $response;
  }

  /**
   * @param $signature
   * @return null|\stdClass
   */
  protected function getBookingFromState($signature) {
    $params = [
      'GetBookingRequest' => [
        'Signature' => $signature,
      ],
    ];

    $response = $this->api->getBookingFromState($params);

    return $response;
  }

  /**
   * Delete specific journeys from current booking.
   *
   * @param $signature
   * @param $journeys
   * @return null|\stdClass
   */
  protected function deleteJourney($signature, $journeys) {
    $params = $this->getDeleteJourneyParams($signature, $journeys);
    $response = $this->api->deleteJourney($params);

    return $response;
  }

  /**
   * Get PDF for current booking.
   *
   * @param $signature
   * @return null|\stdClass
   */
  protected function getPDFTicket($signature) {
    $params = $this->getGetPDFTicketParams($signature);
    $response = $this->api->getPDFTicket($params);

    return $response;
  }

  /**
   * Get RouteData (train number, fare details) for picked route.
   *
   * @return string
   */
  public function getRouteData() {
    $html = [];

    // IT provide info about all classes only for 3h time period.
    // So we make 8 requests and aggregate their.
    // For special class, as example "LowestFareClass", we can get full day timetable.
    foreach ($this->configuration['legs'] as $outerLeg => $legData) {
      if ($outerLeg == 2 && $this->configuration['round_trip']) {
        break;
      }
      $departureStationCode = $legData['departure_station']->getStationCodeBySupplierCode(static::$supplierCode);
      $arrivalStationCode = $legData['arrival_station']->getStationCodeBySupplierCode(static::$supplierCode);
      $departureDate = $legData['departure_date'];
      $params = $this->getGetAvailableTrainsParams($departureStationCode, $arrivalStationCode);
      $startDateTime = DrupalDateTime::createFromTimestamp($departureDate->getTimestamp(), $departureDate->getTimezone());
      $endDateTime = DrupalDateTime::createFromTimestamp($departureDate->getTimestamp(), $departureDate->getTimezone());
      $endDateTime->modify('-1 second');
      $nowTime = time();
      for ($i = 0; $i <= 8; $i++) {
        $journeyDateMarketData = [];
        $endDateTime->modify('+3 hours');
        if ($nowTime <= $endDateTime->getTimestamp() || $this->configuration['round_trip']) {
          $params['GetAvailableTrainsRequest']['GetAvailableTrains']['IntervalStartDateTime'] = $startDateTime->format(DATETIME_DATETIME_STORAGE_FORMAT);
          $params['GetAvailableTrainsRequest']['GetAvailableTrains']['IntervalEndDateTime'] = $endDateTime->format(DATETIME_DATETIME_STORAGE_FORMAT);

          $response = $this->api->getAvailableTrains($params);
          if ($response && !empty($journeyDateMarkets = $response->JourneyDateMarkets)) {
            // JourneyDateMarket can be array if it's roundTrip.
            if (!is_array($journeyDateMarkets->JourneyDateMarket)) {
              $journeyDateMarketData[] = $journeyDateMarkets->JourneyDateMarket;
              $journeyDateMarkets->JourneyDateMarket = $journeyDateMarketData;
            }
            foreach ($journeyDateMarkets->JourneyDateMarket as $journeyDateMarket) {
              $journeyData = [];
              if (!empty($journeys = $journeyDateMarket->Journeys)) {
                // Journey is not array if journey is alone. We need make it universal.
                if (!is_array($journeys->Journey)) {
                  $journeyData[] = $journeys->Journey;
                  $journeys->Journey = $journeyData;
                }
                $output = '';
                foreach ($journeys->Journey as $journey) {
                  if (count($journey->Segments) == 1) {
                    $segment = $journey->Segments->Segment;
                    $output .= '<div><div>Train number: ' . $segment->TrainNumber . '</div>';
                    $output .= '</br>';
                    // Setting coach classes for this train.
                    if (!empty($segment->Fares)) {
                      $fares = $segment->Fares;
                      if (!is_array($fares->Fare)) {
                        $fareData[] = $fares->Fare;
                        $fares->Fare = $fareData;
                      }
                      foreach ($fares->Fare as $fare) {
                        $output .= '<div><div>' . $fare->FareSellKey . '</div>';
                        foreach ($fare as $key => $value) {
                          if ($key != 'PaxFares') {
                            $output .= '<div>';
                            $output .= '<span>' . $key . '</span> : <span>' . $value . '</span>';
                            $output .= '</div>';
                            //$output[$segment->TrainNumber][$fare->FareSellKey][$key] = $value;
                          }
                        }
                        $output .= '</div>';
                        $output .= '</br>';
                      }
                    }
                  }
                  $output .= '</div>';
                  $output .= '</br>';
                  $html[] = $output;
                }
              }
            }
          }
        }
        $startDateTime->modify('+3 hours');
      }
    }

    $result = '';
    foreach ($html as $output) {
      $result .= $output;
    }

    return $result;
  }

  /**
   * Gets needed parameters for SoapClient RetrieveProductsCatalog Request to the ProductManager.
   *
   * @return array
   */
  protected function getLoginRequestParameters() {
    return [
      'LoginRequest' => [
        'Login' => [
          'Username' => $this->configuration['username'],
          'Password' => $this->configuration['password'],
          'Domain' => $this->configuration['domain'],
        ],
        'SourceSystem' => $this->configuration['source_system'],
      ],
    ];
  }

  /**
   * Gets needed parameters for SoapClient Product Catalog Request to the ProductManager.
   *
   * @return array
   */
  protected function getProductCatalogRequestParameters() {
    return [
      'RetrieveProductsCatalogRequest' => [
        'Signature' => null,
        'SourceSystem' => $this->configuration['source_system'],
        'ProductType' => 'Prodotto Carnet',
        'Buyer' => 'Agency',
        'Holder' => 'Customer',
        'CarnetType' => 'Base',
      ],
    ];
  }

  /**
   * Gets needed parameters for SoapClient GetAvailableTrains Request to the BookingManager.
   *
   * @param $departureStationCode
   * @param $arrivalStationCode
   * @return array
   */
  protected function getGetAvailableTrainsParams($departureStationCode, $arrivalStationCode) {
    $params = [
      'GetAvailableTrainsRequest' => [
        'Login' => [
          'Username' => $this->configuration['username'],
          'Password' => $this->configuration['password'],
          'Domain' => $this->configuration['domain'],
        ],
        'GetAvailableTrains' => [
          'RoundTrip' => false,
          'DepartureStation' => $departureStationCode,
          'ArrivalStation' => $arrivalStationCode,
          'AdultNumber' => $this->configuration['adult_number'],
          'ChildNumber' => $this->configuration['child_number'],
          'FareClassControl' => 'Default',
          'IsGuest' => true,
          'SourceSystem' => $this->configuration['source_system'],
        ],
      ],
    ];

    if ($this->configuration['round_trip'] && !$this->firstLegWasSkipped) {
      $params['GetAvailableTrainsRequest']['GetAvailableTrains']['RoundTrip'] = true;
      $params['GetAvailableTrainsRequest']['GetAvailableTrains']['RoundtripDepartureStation'] = $arrivalStationCode;
      $params['GetAvailableTrainsRequest']['GetAvailableTrains']['RoundtripArrivalStation'] = $departureStationCode;
    }

    return $params;
  }

  /**
   * @param string $signature
   * @param array $legsResult
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $leg
   * @return array
   */
  protected function getHoldBookingParams($signature, $legsResult, $order, $leg) {
    // @TODO: need modification for multi journeys.
    $passengerData = $this->getPassengerData($order, $leg);
    $journeyData = $this->getJourneyData($legsResult, $order, $leg);
    return [
      'HoldBookingRequest' => [
        'Signature' => $signature,
        'Journeys' => [
          'Journey' => $journeyData,
        ],
        'Passengers' => [
          'Passenger' => $passengerData,
        ],
      ],
    ];
  }

  /**
   * @param string $signature
   * @return array
   */
  protected function getManagePaymentParams($signature) {
    if ($this->configuration['payment_methods']['enabled'] == 'external') {
      return [
        'ManagePaymentRequest' => [
          'Signature' => $signature,
          'Payment' => [
            'PaymentMethodType' => $this->configuration['payment_methods']['external']['payment_method_type'],
            'PaymentMethodCode' => $this->configuration['payment_methods']['external']['payment_method_code'],
            'QuotedCurrencyCode' => $this->configuration['payment_methods']['external']['quoted_currency_code'],
            'AccountNumber' => $this->configuration['payment_methods']['external']['account_number'],
            'Expiration' => $this->configuration['payment_methods']['external']['expiration'],
            'PaymentFields' => [
              'PaymentField' => [
                [
                  'FieldName' => 'CC::VerificationCode',
                  'FieldValue' => $this->configuration['payment_methods']['external']['verification_code'],
                ],
                [
                  'FieldName' => 'CC::AccountHolderName',
                  'FieldValue' => $this->configuration['payment_methods']['external']['account_holder_name'],
                ]
              ]
            ]
          ],
        ],
      ];
    }
    else {
      return [
        'ManagePaymentRequest' => [
          'Signature' => $signature,
          'Payment' => [
            'PaymentMethodType' => $this->configuration['payment_methods']['agency']['payment_method_type'],
            'PaymentMethodCode' => $this->configuration['payment_methods']['agency']['payment_method_code'],
            'QuotedCurrencyCode' => $this->configuration['payment_methods']['agency']['quoted_currency_code'],
            'AccountNumber' => $this->configuration['payment_methods']['agency']['account_number'],
          ],
        ],
      ];
    }
  }

  /**
   * @param string $signature
   * @param \Drupal\store\Entity\StoreOrder $order
   * @return array
   */
  protected function getFinalizeBookingParams($signature, $order, $passengerData) {
    return [
      'FinalizeBookingRequest' => [
        'Signature' => $signature,
        'SourceSystem' => $this->configuration['source_system'],
        'IdPartner' => $this->configuration['id_partner'],
        'Passengers' => [
          'Passenger' => $passengerData,
        ],
        'BookingContact' => [
          'DistributionOption' => 'Mail',
          'EmailAddress' => $this->configuration['email'],
          'Culture' => 'English',
        ],
      ],
    ];
  }

  /**
   * @param array $bookingData
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $pickedLeg
   * @return array
   */
  protected function getGetBookingParams($bookingData, $order, $pickedLeg) {
    return [
      'GetBookingRequest' => [
        'Login' => [
          'Username' => $this->configuration['username'],
          'Password' => $this->configuration['password'],
          'Domain' => $this->configuration['domain'],
        ],
        'PNR' => $bookingData[$pickedLeg]['bookingKey'],
        'FirstName' => $bookingData[$pickedLeg]['firstName'],
        'LastName' => $bookingData[$pickedLeg]['lastName'],
        'SourceSystem' => $this->configuration['source_system'],
      ],
    ];
  }

  /**
   * @param $signature
   * @param $paymentID
   * @return array
   */
  protected function getCancelBookingParams($signature, $paymentID) {
    return [
      'ManagePaymentRequest' => [
        'Signature' => $signature,
        'Payment' => [
          'PaymentMethodType' => 'Refund',
          'PaymentID' => $paymentID,
        ],
      ],
    ];
  }

  /**
   * @param $signature
   * @param $journeys
   * @return array
   */
  protected function getDeleteJourneyParams($signature, $journeys) {
    return [
      'DeleteJourneyRequest' => [
        'Signature' => $signature,
        'Journeys' => [
          'Journey' => $journeys,
        ]
      ]
    ];
  }

  /**
   * @param $signature
   * @return array
   */
  protected function getGetPDFTicketParams($signature) {
    return [
      'GetPDFTicketRequest' => [
        'Signature' => $signature,
      ]
    ];
  }

  /**
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $leg
   * @return array
   */
  protected function getPassengerData($order, $leg) {
    $adultNumber = (int) $this->configuration['adult_number'];
    $counter = 0;
    $passengerData = [];
    $tickets = $order->getTickets();
    /** @var \Drupal\train_base\Entity\TrainTicket $ticket */
    foreach ($tickets as $ticket) {
      // @TODO: IT doesn't support roundTrip for different passengers.
      // So passengers data for second leg is same.
      if ($ticket->getLegNumber() == $leg) {
        $passengers = $ticket->getPassengers();
        /** @var \Drupal\train_base\Entity\Passenger $passenger */
        foreach ($passengers as $passenger) {
          $counter++;
          $paxType = $counter <= $adultNumber ? 'ADT' : 'CHD';
          $passengerData[] = [
            'FirstName' => $passenger->getFirstName(),
            'LastName' => $passenger->getLastName(),
            'PaxType' => $paxType,
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
  protected function getJourneyData($legsResult, $order, $leg) {
    $journeyData = [];

    foreach ($legsResult as $innerLeg => $legResult) {
      if ($order->getTripType() == 'complex' && $innerLeg == 2 && $leg == 1) {
        break;
      }
      if ($order->getTripType() == 'complex' && $innerLeg == 1 && $leg == 2) {
        continue;
      }
      /** @var \Drupal\it_train_provider\TrainInfoHolder $trainInfoHolder */
      $trainInfoHolder = $legResult['train_info_holder'];
      /** @var \Drupal\it_train_provider\CoachClassInfoHolder $classInfoHolder */
      $classInfoHolder = $legResult['coach_class_info_holder'];
      $journeyData[] = [
        'JourneySellKey' => $trainInfoHolder->getJourneySellKey(),
        'FareSellKey' => $classInfoHolder->getFareSellKey(),
      ];
    }

    return $journeyData;
  }

  protected function addPaymentMethods(array &$form, FormStateInterface $form_state) {
    $form['payment_methods'] = [
      '#type' => 'details',
      '#title' => $this->t('Payment methods'),
      '#tree' => true,
    ];
    $form['payment_methods']['enabled'] = [
      '#type' => 'select',
      '#title' => $this->t('Enabled payment method'),
      '#options' => ['agency' => $this->t('Agency'), 'external' => $this->t('External')],
      '#default_value' => $this->configuration['payment_methods']['enabled'],
    ];
    $form['payment_methods']['agency'] = [
      '#type' => 'details',
      '#title' => $this->t('Agency payment method credits'),
    ];
    $form['payment_methods']['agency']['payment_method_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payment Method Type'),
      '#default_value' => $this->configuration['payment_methods']['agency']['payment_method_type'],
    ];
    $form['payment_methods']['agency']['payment_method_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payment Method Code'),
      '#default_value' => $this->configuration['payment_methods']['agency']['payment_method_code'],
    ];
    $form['payment_methods']['agency']['quoted_currency_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quoted Currency Code'),
      '#default_value' => $this->configuration['payment_methods']['agency']['quoted_currency_code'],
    ];
    $form['payment_methods']['agency']['account_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account Number'),
      '#default_value' => $this->configuration['payment_methods']['agency']['account_number'],
    ];
    $form['payment_methods']['external'] = [
      '#type' => 'details',
      '#title' => $this->t('External payment method credits'),
    ];
    $form['payment_methods']['external']['payment_method_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payment Method Type'),
      '#default_value' => $this->configuration['payment_methods']['external']['payment_method_type'],
    ];
    $form['payment_methods']['external']['payment_method_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payment Method Code'),
      '#default_value' => $this->configuration['payment_methods']['external']['payment_method_code'],
    ];
    $form['payment_methods']['external']['quoted_currency_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quoted Currency Code'),
      '#default_value' => $this->configuration['payment_methods']['external']['quoted_currency_code'],
    ];
    $form['payment_methods']['external']['account_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account Number'),
      '#default_value' => $this->configuration['payment_methods']['external']['account_number'],
    ];
    $form['payment_methods']['external']['expiration'] = [
      '#type' => 'date',
      '#title' => $this->t('Expiration'),
      '#default_value' => $this->configuration['payment_methods']['external']['expiration'],
    ];
    $form['payment_methods']['external']['verification_code'] = [
      '#type' => 'number',
      '#title' => $this->t('Verification Code'),
      '#default_value' => $this->configuration['payment_methods']['external']['verification_code'],
    ];
    $form['payment_methods']['external']['account_holder_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account Holder Name'),
      '#default_value' => $this->configuration['payment_methods']['external']['account_holder_name'],
    ];
  }

}