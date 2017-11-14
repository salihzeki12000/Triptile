<?php

namespace Drupal\express3_train_provider\Plugin\TrainProvider;

use DateInterval;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\express3_train_provider\Express3Api;
use Drupal\train_provider\AvailableRoutesFormTrait;
use Drupal\train_provider\AvailableStationsFormTrait;
use Drupal\train_provider\CoachClassInfoHolder;
use Drupal\train_provider\OrderDepthCacheLifetimeFormTrait;
use Drupal\train_provider\TrainInfoHolder;
use Drupal\train_provider\TrainProviderBase;

/**
 * Provides Express3 Train Provider.
 *
 * @TrainProvider(
 *   id = "express3_train_provider",
 *   label = @Translation("Express3 train provider"),
 *   description = @Translation("Express3 integration."),
 *   operations_provider = "Drupal\train_provider\Plugin\TrainProvider\PluginOperationsProvider",
 *   price_updater = true
 * )
 */
class Express3TrainProvider extends TrainProviderBase {

  use DependencySerializationTrait, AvailableRoutesFormTrait, AvailableStationsFormTrait, OrderDepthCacheLifetimeFormTrait;

  /**
   * Seat type codes.
   */
  const
    SEAT_CODE = 'S',
    BED_CODE = 'BED',
    CABIN_BED_CODE = 'C',
    CABIN_ANOTHER_CODE = 'C*';

  /**
   * Degrading map (reduces total amount of coach class codes)
   */
  protected $coachClassDegradingMap = array(
    '2К' => '2Л',
    '2У' => '2Л',
    '2И' => '2Л',
    '2Э' => '2Т',
    '3Э' => '3П',
    '3У' => '3П',
    '3Л' => '3П',
    '1Б' => '1У',
  );

  /**
   * Express3 Trains supplier code.
   *
   * @var string
   */
  protected static $supplierCode = 'E3';

  /**
   * Express3 Trains train class code.
   *
   * @var string
   */
  protected static $trainClassCode = 'ПАСС';

  /**
   * The value of cache lifetime for searches whose order depth is out from the list.
   *
   * @var int
   */
  protected static $cacheMaxLifetime = 1800; // 30 minutes;

  /**
   * Fallback value for maxDaysBeforeDeparture config.
   *
   * @var int
   */
  protected static $maxDaysBeforeDeparture = 45;

  /**
   * @var \Drupal\express3_train_provider\Express3Api
   */
  protected $api;

  /**
   * Count of passengers.
   *
   * @var int
   */
  protected $pax;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = $this->configuration ? : $this->defaultConfiguration();
    $this->api = new Express3Api($this->configuration);
    $this->logger = \Drupal::logger('express3_train_provider');
    if (isset($this->configuration['adult_number']) && isset($this->configuration['child_number'])) {
      $this->pax = $this->configuration['adult_number'] + $this->configuration['child_number'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['max_days_before_departure'] = static::$maxDaysBeforeDeparture;
    $config['use_local_file'] = true;
    $config['ignore_cache'] = false;
    $config['long_cache'] = [
      'max_routes' => 10,
      'from' => 0,
      'to' => 3,
      'lifetime' => 72000, // 20 hours;
    ];
    return $config;
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
    $form['use_local_file'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use local file'),
      '#default_value' => $this->useLocalFile(),
    ];
    $form['ignore_cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore cache'),
      '#default_value' => $this->ignoreCache(),
    ];
    $form['long_cache'] = [
      '#type' => 'fieldset',
      '#tree' => true,
      '#title' => $this->t('Long cache'),
    ];
    $form['long_cache']['max_routes'] = [
      '#type' => 'number',
      '#title' => $this->t('Max popular routes'),
      '#default_value' => $this->configuration['long_cache']['max_routes'],
    ];
    $form['long_cache']['from'] = [
      '#type' => 'number',
      '#title' => $this->t('From'),
      '#default_value' => $this->configuration['long_cache']['from'],
    ];
    $form['long_cache']['to'] = [
      '#type' => 'number',
      '#title' => $this->t('To'),
      '#default_value' => $this->configuration['long_cache']['to'],
    ];
    $form['long_cache']['lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('Lifetime'),
      '#default_value' => $this->configuration['long_cache']['lifetime'],
    ];
    $this->getCacheSettingsForm($form, $form_state);
    $this->getAvailableRoutesSettingsForm($form, $form_state);
    $this->getAvailableStationsSettingsForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['live'] = $values['live'];
      $this->configuration['use_local_file'] = $values['use_local_file'];
      $this->configuration['ignore_cache'] = $values['ignore_cache'];

      // Available routes.
      $availableRoutes = [];
      if (isset($form['routes_fieldset']['available_routes'])) {
        $availableRoutes = $form_state->getValue($form['routes_fieldset']['available_routes']['#parents']);
      }
      $this->configuration['available_routes'] = $availableRoutes;

      // Available stations.
      $availableRoutes = [];
      if (isset($form['available_stations_fieldset']['routes'])) {
        if ($availableRoutes = $form_state->getValue($form['available_stations_fieldset']['routes']['#parents'])) {
          foreach ($availableRoutes as &$availableRoute) {
            unset($availableRoute['stations']['actions']);
          }
        }
      }
      $this->configuration['available_stations'] = $availableRoutes;

      // List of cache.
      $listOfCache = [];
      if (isset($form['cache_fieldset']['cache'])) {
        if ($listOfCache = $form_state->getValue($form['cache_fieldset']['cache']['#parents'])) {
          foreach ($listOfCache as &$cache) {
            $cache['lifetime'] *= 60;
          }
        }
      }
      $this->configuration['cache'] = $listOfCache;

      $this->configuration['long_cache'] = $values['long_cache'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeTable($longCache = false) {
    $trains = [1 => [], 2 => []];

    foreach ($this->configuration['legs'] as $leg => $legData) {

      // Don't make search if searching is earlier thane today + min_departure_window.
      $min_departure_window = $this->getDaysNumberBeforeDeparture($this->getDepartureDate($leg));
      if ($min_departure_window < $this->getMinDaysBeforeDeparture() || $min_departure_window < $this->getCommonMinDaysBeforeDeparture()) {
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

      // Stations must be enabled.
      if (!$this->getDepartureStation($leg)->isEnabled() || !$this->getArrivalStation($leg)->isEnabled()) {
        continue;
      }

      $departureStationCode = $this->getDepartureStation($leg)->getStationCodeBySupplierCode(static::$supplierCode);
      $arrivalStationCode = $this->getArrivalStation($leg)->getStationCodeBySupplierCode(static::$supplierCode);

      // Search should be made only for stations which have station code for this provider.
      if (!$departureStationCode || !$arrivalStationCode) {
        continue;
      }

      $trainInfoHolders = $this->executeSearch($departureStationCode, $arrivalStationCode, $leg, $longCache);
      if ($trainInfoHolders) {
        $trains[$leg] = $trainInfoHolders;
      }
    }

    return $trains;
  }

  /**
   * Get TrainInfoHolder.
   *
   * @param $trainData
   * @param $departureStation
   * @param $arrivalStation
   * @param $leg
   * @return \Drupal\train_provider\TrainInfoHolder
   */
  protected function convertTrainInfo($trainData, $departureStation, $arrivalStation, $leg) {
    // Initialize TrainInfoHolder.
    $trainInfoHolder = new TrainInfoHolder();

    // Train can doesn't exist in local db, so need to set all possible values.
    if ($train = $this->getTrainByNumber($trainData->n1)) {
      // @TODO: Add status property to the train and check it.
      $trainInfoHolder->setTrain($train);
    }
    else {
      $trainClassCode = !empty($trainData->kn) ? trim($trainData->kn) : static::$trainClassCode;
      $trainInfoHolder->setTrainNumber($trainData->n1);
      $supplier = $this->getSupplierByCode(static::$supplierCode);
      $trainInfoHolder->setSupplier($supplier);
      $trainClass = $this->getTrainClass($supplier, $trainClassCode);
      $trainInfoHolder->setTrainClass($trainClass);
    }

    // Set departure and arrival stations, received from API (not from search).
    $trainInfoHolder->setDepartureStation($departureStation);
    $trainInfoHolder->setArrivalStation($arrivalStation);

    // Setting train Departure time and Arrival time, also calculate manually Running time.
    $departureDatetime = $this->createDatetime($trainData->d, $trainData->t1, $departureStation->getTimezone());
    $arrivalDatetime = $this->createDatetime($trainData->d1, $trainData->t4, $arrivalStation->getTimezone());

    list($runHour, $runMin) = explode('.', (string)$trainData->t3);
    $trainInfoHolder->setRunningTime($runHour * 3600 + $runMin * 60);
    $trainInfoHolder->setDepartureDateTime($departureDatetime);
    $trainInfoHolder->setArrivalDateTime($arrivalDatetime);
    $trainInfoHolder->setDepartureTime($departureDatetime->getTimestamp() - $this->getDepartureDate($leg)->getTimestamp());

    // Setting coach classes for this train.
    $coachClassInfoHolders = [];
    if (!empty($trainData->ck)) {
      foreach ($trainData->ck as $carData) {

        // $carData->cv (seats) is not array if seat is alone. We need to use general approach.
        $carDataInfo = [];
        if (!is_array($carData->cv)) {
          $carDataInfo[] = $carData->cv;
          $carData->cv = $carDataInfo;
        }

        $coachClassInfoHolder = $this->convertCoachClassInfo($trainData, $carData, $trainInfoHolder->getTrainNumber(), $leg);
        if (!empty($coachClassInfoHolder)) {
          $coachClassInfoHolders[] = $coachClassInfoHolder;
        }
      }
    }
    if (empty($coachClassInfoHolders)) {
      return null;
    }

    $coachClassInfoHolders = $this->mergeCoachClassInfoHolders($coachClassInfoHolders);
    $this->sortCoachClassInfoHolders($coachClassInfoHolders);
    $trainInfoHolder->setCoachClasses($coachClassInfoHolders);

    // Set ticket issue date
    if ($leg == 1) {
      $orderDepth = $this->getDaysNumberBeforeDeparture($this->getDepartureDate($leg));
      $ticketIssueDate = DrupalDateTime::createFromTimestamp(strtotime(date('Y-m-d')));
      if ($orderDepth > 0) {
        $ticketIssueDate->modify('+1days');
      }
      $trainInfoHolder->setTicketIssueDate($ticketIssueDate);
    }

    return $trainInfoHolder;
  }

  /**
   * Gets array of CoachClassInfoHolder
   *
   * @param $trainData
   * @param $carData
   * @param string $trainNumber
   * @param $leg
   * @return CoachClassInfoHolder
   */
  protected function convertCoachClassInfo($trainData, $carData, $trainNumber, $leg) {
    $supplier = $this->getSupplierByCode(static::$supplierCode);
    /** @var \Drupal\store\PriceRule $priceRule */
    $priceRule = \Drupal::service('store.price_rule');
    $orderDepth = $this->getDaysNumberBeforeDeparture($this->getDepartureDate($leg));
    $currencyCode = \Drupal::service('store.default_currency')->getUserCurrency();

    $coachClassCode = isset($this->coachClassDegradingMap[$carData->co]) ? $this->coachClassDegradingMap[$carData->co] : $carData->co;
    if (!$coachClass = $this->getCoachClassByCodeAndSupplier($coachClassCode, $supplier)) {
      return null;
    }

    if ($coachClassDependOnTrain = $this->getCoachClassByCodeAndSupplier($coachClassCode . '_' . $trainNumber, $supplier)) {
      $coachClass = $coachClassDependOnTrain;
    }

    // Getting price from coachClassData;
    $price = !empty($carData->tf2) ? $carData->tf2 : $carData->tf;

    // Skip carData without price.
    if ($price <= 0.0) {
      return null;
    }

    // Getting a capacity of the coach.
    $seatCapacity = isset($carData->qm) ? 4 : (isset($carData->dm) ? 2 : 1);
    $seatCode = isset($carData->cv->m4) ? self::SEAT_CODE : (1 < $seatCapacity ? self::CABIN_BED_CODE : self::BED_CODE);
    if (in_array($carData->co, ['1Рђ', '1Рњ', '1Рќ', '1Р�'])) {
      $seatCode = self::CABIN_BED_CODE;
    }

    $countOfSeats = 0;
    foreach (['m4', 'm5', 'm6', 'm7', 'm8'] as $mSeatCode ) {
      foreach ( $carData->cv as $car ) {
        if (!isset($car->$mSeatCode)) {
          continue;
        }
        $countOfSeats += (int)$car->$mSeatCode;
      }
    }

    // Skip unnecessary coach classes and coach classes without seats.
    if (!$this->checkCoachClassAvailability($trainData, $carData, $countOfSeats)) {
      return null;
    }

    $countOfSeats /= $seatCapacity;

    /** @var \Drupal\store\Price $price */
    $price = \Drupal::service('store.price')->get($price, 'RUB');
    $coachClassInfoHolder = new CoachClassInfoHolder();
    $coachClassInfoHolder->setOriginalPrice($price);
    $coachClassInfoHolder->setCoachClass($coachClass);
    $coachClassInfoHolder->setPluginId($this->pluginId);
    $coachClassInfoHolder->setSeatType($this->getSeatType($supplier, $seatCode));
    $coachClassInfoHolder->setCarServices($this->getCarServices($supplier, $carData->r));
    $coachClassInfoHolder->setCountOfAvailableTickets($countOfSeats);

    // Switch price to user currency.
    $updatedPrice = $price->convert($currencyCode);

    // Apply 'before display' price rules
    $priceRuleData = [
      'train' => $trainNumber,
      'supplier' => $supplier,
      'order_depth' => $orderDepth,
    ];
    $updatedPrice = $priceRule->updatePrice('before_display', $updatedPrice, $priceRuleData)['price'];
    $coachClassInfoHolder->setPrice($updatedPrice);

    return $coachClassInfoHolder;
  }

  /**
   * Merge equal coach class info holders with same coach class, seat type and services.
   * @param CoachClassInfoHolder[] $coachClassInfoHolders
   * @return CoachClassInfoHolder[]
   */
  protected function mergeCoachClassInfoHolders($coachClassInfoHolders) {
    $newCoachClassInfoHolders = array();
    /** @var CoachClassInfoHolder $coachClassInfoHolder */
    foreach ($coachClassInfoHolders as $coachClassInfoHolder) {
      $add = true;
      $equalToKey = -1;
      // Look for equal tickets using 3 criteria - coach class, seat class, car services.
      /** @var CoachClassInfoHolder $newCoachClassInfoHolder */
      foreach ($newCoachClassInfoHolders as $key => $newCoachClassInfoHolder) {
        $coachClassEqual = $coachClassInfoHolder->getCoachClass()->id() == $newCoachClassInfoHolder->getCoachClass()->id();
        $seatTypeEqual = $coachClassInfoHolder->getSeatType()->id() == $newCoachClassInfoHolder->getSeatType()->id();
        $carServiceEqual = count($coachClassInfoHolder->getCarServices()) == count($newCoachClassInfoHolder->getCarServices());
        if ($carServiceEqual) {
          $countOfEquals = 0;
          /** @var \Drupal\train_base\Entity\CarService $service */
          foreach ($coachClassInfoHolder->getCarServices() as $service) {
            /** @var \Drupal\train_base\Entity\CarService $newService */
            foreach ($newCoachClassInfoHolder->getCarServices() as $newService) {
              if ($service->id() == $newService->id()) {
                $countOfEquals++;
                break;
              }
            }
          }
          if ($countOfEquals != count($coachClassInfoHolder->getCarServices())) {
            $carServiceEqual = false;
          }
        }
        $add = !($coachClassEqual && $seatTypeEqual && $carServiceEqual);
        if (!$add) {
          $equalToKey = $key;
          break;
        }
      }

      if ($add) {
        // Add the coach class info holder to result.
        $newCoachClassInfoHolders[] = $coachClassInfoHolder;
      }
      else {
        // Set maximal price for equal coach class info holders, update available.
        /** @var CoachClassInfoHolder $newCoachClassInfoHolder */
        $newCoachClassInfoHolder = $newCoachClassInfoHolders[$equalToKey];
        if ($newCoachClassInfoHolder->getOriginalPrice()->lessThan($coachClassInfoHolder->getOriginalPrice())) {
          $newCoachClassInfoHolder->setOriginalPrice($coachClassInfoHolder->getOriginalPrice());
        }
        $newCoachClassInfoHolder->addCountOfAvailableTickets($coachClassInfoHolder->getCountOfAvailableTickets());
      }
    }

    return $newCoachClassInfoHolders;
  }

  /**
   * @param \Drupal\train_base\Entity\Supplier $supplier
   * @param $servicesData
   * @return \Drupal\train_base\Entity\CarService[] array
   */
  protected function getCarServices($supplier, $servicesData) {
    $carServices = [];

    // $serviceData can be empty stdClass, exit in such case.
    if (empty($servicesData) || empty((array)$servicesData)) {
      return $carServices;
    }
    $services = trim((string)$servicesData);

    // After trim string can be empty too, exit in such case.
    if (empty($services)) {
      return $carServices;
    }
    if (false === strpos($services, ' ')) {
      $services = [$services];
    }
    else {
      $services = explode(' ', $services);
    }
    foreach ($services as $service) {
      $carService = $this->getCarService($supplier, $service);
      if ($carService) {
        $carServices[] = $carService;
      }
    }

    return $carServices;
  }

  /**
   * Search execute. Prepare search parameters, also includes cache level.
   *
   * @param $departureStationCode
   * @param $arrivalStationCode
   * @param $leg
   * @param $longCache
   * @return false|mixed|object
   */
  protected function executeSearch($departureStationCode, $arrivalStationCode, $leg, $longCache) {
    $trains = [];
    $paramsSet = $this->prepareSearchParams([
      'departure_station' => $departureStationCode,
      'arrival_station' => $arrivalStationCode,
      'departure_date' => $this->getDepartureDate($leg)->format('dm')
    ]);

    foreach ($paramsSet as $params) {
      $cached = true;

      $cacheKey = $this->buildCacheKey([
        $this->getPluginDefinition()['id'],
        $params['departure_station'],
        $params['arrival_station'],
        $this->getDepartureDate($leg)->getTimestamp()
      ]);
      $longCacheKey = $this->buildCacheKey([
        $this->getPluginDefinition()['id'],
        $params['departure_station'],
        $params['arrival_station'],
        'long_cache',
      ]);

      $responseXml = $this->readResponseCache($cacheKey, $longCacheKey);

      if ($this->ignoreCache() || $responseXml === false) {
        $responseXml = $this->sendSearchRequest($params);

        if (!isset($responseXml->n)) {
          continue;
        }
        if (!empty($responseXml->IP) || !empty($responseXml->MN)) {
          continue;
        }
        elseif (!empty($responseXml->BD)) {
          $this->logger->error('Express3 error: wrong date.');
          continue;
        }
        elseif (!empty($responseXml->SN)) {
          $this->logger->error($this->t('Express3 error: no station name beginning with @station_name.', ['station_name' => $responseXml->SN]));
          continue;
        }
        elseif (!empty($responseXml->UC)) {
          $this->logger->error('Express3 error: specify station.');
          continue;
        }
        elseif (!empty($responseXml->O)) {
          $this->logger->error('Express3 error: unknown error.');
          continue;
        }
        elseif (!empty($responseXml->B)) {
          $this->logger->error('Express3 error: error while processing request.');
          continue;
        }
        $cached = false;
      }

      $response = json_decode(json_encode($responseXml));

      // $response->n (trains) is not array if train is alone. We have to make it universal.
      if (!is_array($response->n)) {
        $trainData[] = $response->n;
        $response->n = $trainData;
      }
      foreach ($response->n as $trainInfo) {
        // $trainInfo->ck (coaches) is not array if coach is alone. We have to it universal.
        if (!is_array($trainInfo->ck)) {
          $coachData[] = $trainInfo->ck;
          $trainInfo->ck = $coachData;
        }
        $departureStation = $this->getDepartureE3Station($params['departure_station'], $trainInfo, $leg, $params['departure_station'] != $departureStationCode);
        $arrivalStation = $this->getArrivalE3Station($params['arrival_station'], $trainInfo, $leg, $params['arrival_station'] != $arrivalStationCode);
        if ($departureStation && $arrivalStation) {
          if ($train = $this->convertTrainInfo($trainInfo, $departureStation, $arrivalStation, $leg)) {
            $trains[] = $train;
          }
        }
      }

      if (!empty($trains)) {
        if ($longCache) {
          $lifetime = $this->configuration['long_cache']['lifetime'];
          $this->writeResponseCache($longCacheKey, $responseXml, $lifetime);
        }
        elseif (!$cached) {
          $lifetime = $this->getCacheLifetime($this->getDepartureDate($leg));
          if ($lifetime != 0) {
            $this->writeResponseCache($cacheKey, $responseXml, $lifetime);
          }
        }
      }
    }

    return $trains;
  }

  /**
   * Directly request to API. Can be recursive while response requires new request.
   *
   * @param $params
   * @return mixed
   */
  protected function sendSearchRequest($params) {
    $response = $this->api->getTimetable($params);

    if (isset($response->u)) {
      $lastDepartureTime = 0;
      foreach ($response->n as $trainInfo) {
        // avoid using max() to keep string values
        $lastDepartureTime = floatval($trainInfo->t1) > floatval($lastDepartureTime) ? $trainInfo->t1 : $lastDepartureTime;
      }
      $timeFrom = DrupalDateTime::createFromFormat('H.i', $lastDepartureTime);
      $timeFrom->add(new DateInterval('PT1M'));
      $params['time_from'] = $timeFrom->format('Hi');
      $params['time_to'] = '2359';

      $followupResponse = $this->sendSearchRequest($params);
      $responseDom = dom_import_simplexml($response);
      $followupResponseDom = dom_import_simplexml($followupResponse);
      foreach ($followupResponseDom->getElementsByTagName('n') as $trainInfo) {
        $trainInfo = $responseDom->ownerDocument->importNode($trainInfo, true);
        $responseDom->appendChild($trainInfo);
      }

      unset($followupResponse);
    }

    return $response;
  }

  /**
   * Find station code by station name.
   *
   * @param $stationCode
   * @param $trainInfo
   * @param $leg
   * @param $override
   * @return \Drupal\train_base\entity\Station|null
   */
  protected function getDepartureE3Station($stationCode, $trainInfo, $leg, $override) {
    $departureStation = null;
    if ($override) {
      $departureStation = $this->getStationByCode($stationCode);
    }
    else {
      if ($this->getDepartureStation($leg)->getStationChildrenIds()) {
        foreach ($trainInfo->ck as $coach) {
          if (isset($coach->vok->c1)) {
            $departureStation = $this->getStationByCode($coach->vok->c1);
          }
          else {
            if (isset($trainInfo->np->c[0])) {
              if ($code = $this->findE3Station($trainInfo->np->c[0])) {
                $departureStation = $this->getStationByCode($code);
              }
            }
          }
          break;
        }
      }
      else {
        $departureStation = $this->getDepartureStation($leg);
      }
    }

    return $departureStation;
  }

  /**
   * Find station code by station name.
   *
   * @param $stationCode
   * @param $trainInfo
   * @param $leg
   * @param $override
   * @return \Drupal\train_base\entity\Station|null
   */
  protected function getArrivalE3Station($stationCode, $trainInfo, $leg, $override) {
    $arrivalStation = null;
    if ($override) {
      $arrivalStation = $this->getStationByCode($stationCode);
    }
    else {
      if ($this->getArrivalStation($leg)->getStationChildrenIds()) {
        foreach ($trainInfo->ck as $coach) {
          if (isset($coach->vok->c2)) {
            $arrivalStation = $this->getStationByCode($coach->vok->c2);
          }
          else {
            if (isset($trainInfo->np->c[1])) {
              if ($code = $this->findE3Station($trainInfo->np->c[1])) {
                $arrivalStation = $this->getStationByCode($code);
              }
            }
          }
          break;
        }
      }
      else {
        $arrivalStation = $this->getArrivalStation($leg);
      }
    }

    return $arrivalStation;
  }

  /**
   * @param $date
   * @param $time
   * @return DrupalDateTime
   */
  protected function createDatetime($date, $time, $timezone) {
    list($day, $month) = explode('.', (string)$date);
    list($hour, $min) = explode('.', (string)$time);
    $year = date('Y');
    if (($month < date('n')) || ($month == date('n') && $day < date('j'))) {
      $year = date('Y') + 1;
    }

    return new DrupalDateTime("$year-$month-$day $hour:$min:00", $timezone);
  }

  /**
   * Reads response from cache.
   *
   * @param $key
   * @param $longCacheKey
   * @return false|object
   */
  protected function readResponseCache($key, $longCacheKey) {
    $result = $this->cacheBackend->get($key, true);

    if ($result && $result->valid && isset($result->data)) {
      return simplexml_load_string($result->data);
    }
    else {
      $longCacheParams = $this->configuration['long_cache'];
      $date = new DrupalDateTime(date('Y-m-d'));
      if (time() - $date->getTimestamp() > $longCacheParams['from'] && time() - $date->getTimestamp() < $longCacheParams['to']) {
        $result = $this->cacheBackend->get($longCacheKey, true);
        if ($result && $result->valid && isset($result->data)) {
          return simplexml_load_string($result->data);
        }
      }
    }

    return false;
  }

  /**
   * Writes response to cache.
   *
   * @param $key
   * @param \SimpleXMLElement $responseXml
   * @param int $lifetime
   */
  protected function writeResponseCache($key, $responseXml, $lifetime) {
    $lifetime = time() + $lifetime;
    $this->cacheBackend->set($key, $responseXml->asXML(), $lifetime);
  }

  /**
   * Get cache lifetime based on order depth.
   *
   * @param DrupalDateTime $departureDate
   * @return int
   */
  protected function getCacheLifetime($departureDate) {
    $orderDepth = $this->getDaysNumberBeforeDeparture($departureDate);
    $lifetime = static::$cacheMaxLifetime;
    if (isset($this->configuration['cache'])) {
      foreach ($this->configuration['cache'] as $cache) {
        if ($orderDepth >= $cache['from'] && $orderDepth <= $cache['to']) {
          $lifetime = $cache['lifetime'];
        }
      }
    }

    return $lifetime;
  }

  /**
   * Returns true, if using local file instead request to the server.
   *
   * @return bool
   */
  protected function useLocalFile() {
    return isset($this->configuration['use_local_file']) ? $this->configuration['use_local_file'] : false;
  }

  /**
   * Build array of search params for cities with few rail stations.
   * @param array $params
   *
   * @return array set of params to search.
   */
  protected function prepareSearchParams($params) {
    // If city have few rail stations, we use rail station codes.
    $newParams = [];
    if (!empty($this->configuration['available_stations'])) {
      $searchByRailStation = $this->configuration['available_stations'];
      foreach ($searchByRailStation as $route) {
        $departureStation = $this->entityTypeManager->getStorage('station')->load($route['departure_station']);
        $departureStationCode = $departureStation->getStationCodeBySupplierCode(static::$supplierCode);
        $arrivalStation = $this->entityTypeManager->getStorage('station')->load($route['arrival_station']);
        $arrivalStationCode = $arrivalStation->getStationCodeBySupplierCode(static::$supplierCode);
        if ($departureStationCode == $params['departure_station'] && $arrivalStationCode == $params['arrival_station']) {
          foreach ($route['stations'] as $stationId) {
            /** @var \Drupal\train_base\entity\Station $station */
            $station = $this->entityTypeManager->getStorage('station')->load($stationId['id']);
            $tmp = $params;
            $tmp['departure_station'] = $station->getStationCodeBySupplierCode(static::$supplierCode);
            $newParams[] = $tmp;
          }
        }
      }
    }
    if (empty($newParams)) {
      $newParams[] = $params;
    }

    return $newParams;
  }

  /**
   * Find station code by station name.
   *
   * @param $stationName
   * @return bool|int|string
   */
  protected function findE3Station($stationName) {
    $express3StationStorage = $this->entityTypeManager->getStorage('express3_station');
    $query = $express3StationStorage->getQuery();
    $query->condition('name', $stationName);
    $stationId = $query->execute();
    if ($stationId) {
      $stationId = reset($stationId);
      /** @var \Drupal\express3_train_provider\Entity\Express3Station $station */
      $station = $express3StationStorage->load($stationId);
      return $station->getCode();
    }

    return false;
  }

  /**
   * Return cache key string
   *
   * @param array  $request
   * @param string $method
   * @return string
   */
  protected function buildCacheKey($request, $method = null) {
    return md5(serialize(null === $method ? $request : array_merge($request, array('__METHOD__' => $method))));
  }

  /**
   * Returns true, if cache is ignored.
   */
  protected function ignoreCache() {
    return isset($this->configuration['ignore_cache']) && $this->configuration['ignore_cache'];
  }

  /**
   * Checking coach class on different conditions.
   *
   * @param $trainData
   * @param $carData
   * @param $countOfSeats
   * @return bool
   */
  protected function checkCoachClassAvailability($trainData, $carData, $countOfSeats) {
    // Decrease count of available seats in economy class with invalid seats. Basically this coach class have 2 invalid seats.
    if ((isset($trainData->brn) && $trainData->brn == 'САПСАН') && ($carData->co == '2С' && $carData->r == ' * И')) {
      $countOfSeats -= 2;
    }

    // Skipp coach class without available seats.
    if ($countOfSeats <= 0 || $countOfSeats < $this->pax) {
      return false;
    }

    // Skip coach class with animals
    if ((isset($trainData->brn) && $trainData->brn == 'САПСАН')
      && (($carData->co == '2В' && $carData->r == ' * Д У1')
        || ($carData->co == '2С' && $carData->r == ' * Ж')
      )) {
      return false;
    }

    return true;
  }

}
