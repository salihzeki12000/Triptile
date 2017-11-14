<?php

namespace Drupal\amadeus_train_provider\Plugin\TrainProvider;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\amadeus_train_provider\AmadeusApi;
use Drupal\train_provider\TrainProviderBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\train_provider\TrainInfoHolder;
use Drupal\amadeus_train_provider\CoachClassInfoHolder;

/**
 * Provides Amadeus Train Provider.
 *
 * @TrainProvider(
 *   id = "amadeus_train_provider",
 *   label = "Amadeus train provider",
 *   description = "Amadeus integration.",
 *   operations_provider = "Drupal\train_provider\Plugin\TrainProvider\PluginOperationsProvider",
 *   price_updater = false
 * )
 */
class AmadeusTrainProvider extends TrainProviderBase {

  /**
   * Italian Trains supplier code.
   *
   * @var string
   */
  protected static $supplierCode = 'IT';

  /**
   * @var \Drupal\amadeus_train_provider\AmadeusApi
   */
  protected $api;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->api = new AmadeusApi($configuration['live']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['live'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Live'),
      '#default_value' => $this->isLive(),
    ];
    $form['reseller_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reseller Code'),
      '#default_value' => $this->configuration['reseller_code'],
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
    $form['lang'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Language'),
      '#default_value' => $this->configuration['lang'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function isLive() {
    return $this->configuration['live'];
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
      $this->configuration['username'] = $values['username'];
      $this->configuration['password'] = $values['password'];
      $this->configuration['reseller_code'] = $values['reseller_code'];
      $this->configuration['lang'] = $values['lang'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeTable() {
    $trains = $passengers = [];

    /*$parm[] = new \SoapVar('', XSD_STRING, null, null, 'passenger');
    $parm[] = new \SoapVar('', XSD_STRING, null, null, 'passenger');
    //$resp = $client->getStuff( new \SoapVar($parm, SOAP_ENC_OBJECT) );

    $passengers[] = ['name' => 'Slava', 'surname' => 'Dergachyov'];
    $passengers[] = ['name' => 'Sergei', 'surname' => 'Bril'];

    $passenger = new \SoapParam($parm, 'passengers');

    $op = new \SoapVar($parm, SOAP_ENC_OBJECT);

    $op = 1;*/
    $params = [
      'access' => $this->getAccessParameters(),
      'originDestinationSearch' => [
        'originCode' => 7010720,
        'originDate' => $this->configuration['departure_date']->format(DATETIME_DATE_STORAGE_FORMAT),
        'originTimeFrom' => '00:00',
        'originTimeTo' => '23:59',
        'destinationCode' => 8799015,
      ],
      'passengers' => [
        'passenger' => [
          [
            'passengerId' => '',
            'salutation' => 'Mr',
            'name' => 'Sergei',
            'surname' => 'Bril',
            'patronymic' => '',
            'documentType' => '',
            'documentNumber' => '',
            'documentExpireDate' => '',
            'birthDate' => '',
            'nationality' => '',
            'residence' => '',
            'email' => '',
            'card' => '',
            'additionalOptions' => '',
          ],
        ],
      ],
    ];
    $response = $this->api->journeySearch($params);
    if (!empty($journeys = $response->journeys)) {
      if (!is_array($journeys->journey)) {
        $journeyData[] = $journeys->journey;
        $journeys->journey = $journeyData;
      }
      foreach ($journeys->journey as $journey) {
        if (count($journey->journeySegments->journeySegment) == 1 && $journey->journeySegments->journeySegment->transportType == 'train') {
          $trains[] = $this->convertTrainInfo($journey);
        }
      }
    }

    return $trains;
  }

  /**
   * Gets TrainInfoHolder for this route.
   *
   * @param $journey
   * @return \Drupal\train_provider\TrainInfoHolder
   */
  protected function convertTrainInfo($journey) {
    // Initialize TrainInfoHolder.
    $trainInfoHolder = new TrainInfoHolder();

    $segment = $journey->journeySegments->journeySegment;

    // Train can doesn't exist in local db, so need to set all possible values.
    if ($train = $this->getTrainByNumber($segment->trainNumber)) {
      $trainInfoHolder->setTrain($train);
    }
    else {
      $trainInfoHolder->setTrainNumber($segment->trainNumber);
      $supplier = $this->getSupplierByCode(static::$supplierCode);
      $trainInfoHolder->setSupplier($supplier);
      $trainInfoHolder->setTrainClass($this->getTrainClass($supplier));
    }

    // Setting departure and arrival stations received from API (not from search).
    $trainInfoHolder->setDepartureStation($this->getStationByCode($journey->journeyInfo->departureCode));
    $trainInfoHolder->setArrivalStation($this->getStationByCode($journey->journeyInfo->arrivalCode));

    // Setting train Departure time and Arrival time, also calculate manually Running time.
    // @todo: Received date and time in the local time. Need to check for clearing working.
    $departure_datetime = DrupalDateTime::createFromFormat(
      'Y-m-d H:i',
      $journey->journeyInfo->departureDate . ' ' . $journey->journeyInfo->departureTime,
      $trainInfoHolder->getDepartureStation()->getTimezone()
    );
    $arrival_datetime = DrupalDateTime::createFromFormat(
      'Y-m-d H:i',
      $journey->journeyInfo->arrivalDate . ' ' . $journey->journeyInfo->arrivalTime,
      $trainInfoHolder->getArrivalStation()->getTimezone()
    );
    $trainInfoHolder->setRunningTime($arrival_datetime->getTimestamp() - $departure_datetime->getTimestamp());
    $trainInfoHolder->setDepartureDateTime($departure_datetime);
    $trainInfoHolder->setArrivalDateTime($arrival_datetime);
    $trainInfoHolder->setDepartureTime($departure_datetime->getTimestamp() - $this->configuration['departure_date']->getTimestamp());

    // Setting coach classes for this train.
    $coach_classes = $this->convertCoachClassInfo($journey->journeyId, $trainInfoHolder->getTrainNumber(), $trainInfoHolder->getSupplier());
    $trainInfoHolder->setCoachClasses($coach_classes);

    return $trainInfoHolder;
  }

  /**
   * Gets CoachClassInfoHolders for this train.
   *
   * @param $journeyId
   * @param string $trainNumber
   * @param \Drupal\train_base\Entity\Supplier $supplier
   * @return \Drupal\amadeus_train_provider\CoachClassInfoHolder[]
   */
  protected function convertCoachClassInfo($journeyId, $trainNumber, $supplier) {
    $passengersNumber = $this->configuration['adult_number'] + $this->configuration['children_number'];
    $fareParams = [
      'access' => $this->getAccessParameters(),
      'journeyId' => $journeyId,
    ];
    $response = $this->api->fareOfferSearch($fareParams);
    $fares = $response->fareOffers;

    $coachClassInfoHolders = $prices = [];
    $price_rule = \Drupal::service('store.price_rule');
    $order_depth = $this->getDaysNumberBeforeDeparture($this->departureDate);
    $currency_code = \Drupal::service('store.default_currency')->getUserCurrency();
    if (!is_array($fares->fareOffer)) {
      $fareData[] = $fares->fareOffer;
      $fares->fareOffer = $fareData;
    }
    foreach ($fares->fareOffer as $fare) {
      $coach_class = null;
      if (!$coach_class = $this->getCoachClassByCode($fare->fareName)) {
        continue;
      }
      // Optional request.
      /*$seatParams = [
        'access' => $this->getAccessParameters(),
        'fareOfferId' => $fare->fareOfferId,
      ];
      $seats = $this->api->carSeatSearch($seatParams);*/
      if (!empty($netFare = $fare->netFare)) {
        $netFare /= $passengersNumber;
        /** @var \Drupal\store\Price $price */
        $price = \Drupal::service('store.price')->get($netFare, $fare->currency);
        $coachClassInfoHolder = new CoachClassInfoHolder();
        $coachClassInfoHolder->setOriginalPrice($price);
        $coachClassInfoHolder->setCoachClass($coach_class);
        //$coachClassInfoHolder->setSeatType($ticket_product->getSeatType());
        /*$car_services = $ticket_product->getCoachClass()->car_service;
        $car_services_array = [];
        foreach ($car_services as $car_service) {
          $car_services_array[] = $car_service->entity;
        }
        $coachClassInfoHolder->setCarServices($car_services_array);
        $coachClassInfoHolder->setCountOfAvailableTickets($ticket_product->max_quantity->value);

        // Switch price to user currency.*/
        $updated_price = $price->convert($currency_code);

        // Implements before display price rules.
        $updated_price = $price_rule->updatePrice('before_display', $updated_price,
          ['train' => $trainNumber, 'supplier' => $supplier, 'order_depth' => $order_depth])['price'];
        $coachClassInfoHolder->setPrice($updated_price);
        $coachClassInfoHolders[] = $coachClassInfoHolder;
      }
    }
    return $coachClassInfoHolders;
  }

  /**
   * Gets needed parameters for SoapClient RetrieveProductsCatalog Request to the ProductManager.
   *
   * @return array
   */
  protected function getAccessParameters() {
    return [
      'resellerCode' => $this->configuration['reseller_code'],
      'userName' => $this->configuration['username'],
      'password' => $this->configuration['password'],
      'lang' => $this->configuration['lang'],
    ];
  }

  /**
   * Gets needed parameters for SoapClient Login Request to the SessionManager.
   *
   * @return array
   */
  protected function getProductCatalogRequestParameters() {
    return [
      'RetrieveProductsCatalogRequest' => [
        'Signature' => null,
        'SourceSystem' => 'FOREIGN_AGENCY',
        'ProductType' => 'Prodotto Carnet',
        'Buyer' => 'Agency',
        'Holder' => 'Customer',
      ],
    ];
  }

  /**
   * Gets needed parameters for SoapClient GetAvailableTrains Request to the BookingManager.
   *
   * @return array
   */
  protected function getBookingRequestParameters() {
    return [
      'GetAvailableTrainsRequest' => [
        'Login' => [
          'Username' => $this->configuration['username'],
          'Password' => $this->configuration['password'],
          'Domain' => $this->configuration['domain'],
        ],
        'GetAvailableTrains' => [
          'RoundTrip' => $this->configuration['round_trip'],
          'DepartureStation' => $this->departureStation->getStationCodeBySupplierCode(static::$supplierCode),
          'ArrivalStation' => $this->arrivalStation->getStationCodeBySupplierCode(static::$supplierCode),
          //'IntervalStartDateTime' => '2017-02-22T03:00:01.000',
          //'IntervalEndDateTime' => '2017-02-22T23:00:01.000',
          'AdultNumber' => $this->configuration['adult_number'],
          'ChildNumber' => $this->configuration['child_number'],
          'FareClassControl' => 'Default',
          //'FareClassControl' => 'LowestFareClass',
          'IsGuest' => true,
          'SourceSystem' => $this->configuration['source_system'],
        ],
      ],
    ];
  }
}