<?php
/**
 * @file
 * Provides Drupal\train_provider\TrainProviderBase.
 */

namespace Drupal\train_provider;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\train_base\Entity\Station;
use Drupal\train_base\Entity\Supplier;

abstract class TrainProviderBase extends PluginBase implements TrainProviderInterface {

  use StringTranslationTrait;

  /**
   * Fallback value for maxDaysBeforeDeparture config.
   *
   * @var int
   */
  protected static $maxDaysBeforeDeparture = -1;

  /**
   * Fallback value for minDaysBeforeDeparture config.
   *
   * @var int
   */
  protected static $minDaysBeforeDeparture = 0;

  /**
   * Fallback value for minHoursBeforeDeparture config.
   *
   * @var int
   */
  protected static $minHoursBeforeDeparture = 1;

  /**
   * Minimal depth in minutes.
   *
   * @var int
   */
  protected static $minimalDepth = 30;

  /**
   * @var string
   */
  protected static $supplierCode = '';

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The cache.default cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $this->entityQuery = \Drupal::service('entity.query');
    $this->cacheBackend = \Drupal::service('cache.train_provider');
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'status' => 1,
      'min_days_before_departure' => static::$minDaysBeforeDeparture,
      'min_hours_before_departure' => static::$minHoursBeforeDeparture,
      'max_days_before_departure' => static::$maxDaysBeforeDeparture,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $this->isEnabled(),
    ];

    $form['min_departure_window_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Minimal departure window'),
    ];
    $form['min_departure_window_fieldset']['min_days_before_departure'] = [
      '#title' => $this->t('Number of days'),
      '#type' => 'number',
      '#default_value' => $this->getMinDaysBeforeDeparture(),
      '#min' => 0,
    ];
    $form['min_departure_window_fieldset']['min_hours_before_departure'] = [
      '#title' => $this->t('Number of hours'),
      '#type' => 'number',
      '#default_value' => $this->getMinHoursBeforeDeparture(),
      '#min' => 0,
      '#step' => 0.01,
    ];
    $form['max_departure_window_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Maximal departure window'),
    ];
    $form['max_departure_window_fieldset']['max_days_before_departure'] = [
      '#title' => $this->t('Number of days'),
      '#type' => 'number',
      '#default_value' => $this->getMaxDaysBeforeDeparture(),
      '#description' => $this->t('For exception this condition from request, set value in -1.'),
      '#min' => -1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateConfigurationForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['status'] = (bool) $values['status'];
      $this->configuration['min_days_before_departure'] = $values['min_days_before_departure'];
      $this->configuration['min_hours_before_departure'] = $values['min_hours_before_departure'];
      if (isset($values['max_days_before_departure'])) {
        $this->configuration['max_days_before_departure'] = $values['max_days_before_departure'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    if (isset($this->configuration['status'])) {
      return $this->configuration['status'];
    }
    else {
      return false;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxDaysBeforeDeparture() {
    if (!empty($this->configuration['max_days_before_departure'])) {
      return $this->configuration['max_days_before_departure'];
    }
    else {
      return static::$maxDaysBeforeDeparture;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMinDaysBeforeDeparture() {
    if (!empty($this->configuration['min_days_before_departure'])) {
      return $this->configuration['min_days_before_departure'];
    }
    else {
      return static::$minDaysBeforeDeparture;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMinHoursBeforeDeparture() {
    if (!empty($this->configuration['min_hours_before_departure'])) {
      return $this->configuration['min_hours_before_departure'];
    }
    else {
      return static::$minHoursBeforeDeparture;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCommonMinDaysBeforeDeparture() {
    if (!empty($this->configuration['common_min_days_before_departure'])) {
      return $this->configuration['common_min_days_before_departure'];
    }
    else {
      return static::$minDaysBeforeDeparture;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getCommonMinHoursBeforeDeparture() {
    if (!empty($this->configuration['common_min_hours_before_departure'])) {
      return $this->configuration['common_min_hours_before_departure'];
    }
    else {
      return static::$minHoursBeforeDeparture;
    }
  }

  /**
   * Get departure station of current leg.
   *
   * @param $leg
   * @return Station|null
   */
  protected function getDepartureStation($leg) {
    if (!empty($this->configuration['legs'][$leg]['departure_station'])) {
      return $this->configuration['legs'][$leg]['departure_station'];
    }
    else {
      return null;
    }
  }

  /**
   * Get arrival station of current leg.
   *
   * @param $leg
   * @return Station|null
   */
  protected function getArrivalStation($leg) {
    if (!empty($this->configuration['legs'][$leg]['arrival_station'])) {
      return $this->configuration['legs'][$leg]['arrival_station'];
    }
    else {
      return null;
    }
  }

  /**
   * Get departure date of current leg.
   *
   * @param $leg
   * @return DrupalDateTime|null
   */
  protected function getDepartureDate($leg) {
    if (!empty($this->configuration['legs'][$leg]['departure_date'])) {
      return $this->configuration['legs'][$leg]['departure_date'];
    }
    else {
      return null;
    }
  }

  /**
   * Return array of available routes for this provider.
   *
   * @return array
   */
  protected function getAvailableRoutes() {
    if (!empty($this->configuration['available_routes'])) {
      return $this->configuration['available_routes'];
    }
    else {
      return [];
    }
  }

  /**
   * Gets Train entity.
   *
   * @param $trainNumber
   * @return \Drupal\train_base\Entity\Train|null
   */
  protected function getTrainByNumber($trainNumber) {
    $train = null;
    $query = \Drupal::entityQuery('train');
    $query->condition('number', $trainNumber);
    $query->range(0, 1);
    $trainId = $query->execute();
    if ($trainId) {
      $trainId = reset($trainId);
      $train = \Drupal::service('entity_type.manager')->getStorage('train')->load($trainId);
    }
    return $train;
  }

  /**
   * Gets Station entity.
   *
   * @param $stationCode
   * @return \Drupal\train_base\Entity\Station|null
   */
  protected function getStationByCode($stationCode) {
    $station = null;
    $query = \Drupal::entityQuery('station');
    $query->condition('supplier_mapping.code', $stationCode);
    $query->range(0, 1);
    $stationId = $query->execute();
    if ($stationId) {
      $stationId = reset($stationId);
      $station = \Drupal::service('entity_type.manager')->getStorage('station')->load($stationId);
    }
    return $station;
  }

  /**
   * Gets CoachClass entity.
   *
   * @param string $coachClassCode
   * @param \Drupal\train_base\Entity\Supplier $supplier
   * @return \Drupal\train_base\Entity\CoachClass|null
   */
  protected function getCoachClassByCodeAndSupplier($coachClassCode, $supplier) {
    $coachClass = null;
    $query = \Drupal::entityQuery('coach_class');
    $query->condition('code', $coachClassCode);
    $query->condition('supplier', $supplier->id());
    $query->condition('status', 1);
    $query->range(0, 1);
    $coachClassId = $query->execute();
    if ($coachClassId) {
      $coachClassId = reset($coachClassId);
      $coachClass = \Drupal::service('entity_type.manager')->getStorage('coach_class')->load($coachClassId);
    }
    return $coachClass;
  }

  /**
   * Gets Supplier entity based on TrainProvider supplierCode.
   *
   * @param string $supplierCode
   * @return \Drupal\train_base\Entity\Supplier|null
   */
  protected function getSupplierByCode($supplierCode) {
    $supplier = null;
    $query = \Drupal::entityQuery('supplier');
    $query->condition('code', $supplierCode);
    $query->condition('status', 1);
    $query->range(0, 1);
    $supplierId = $query->execute();
    if ($supplierId) {
      $supplierId = reset($supplierId);
      $supplier = \Drupal::service('entity_type.manager')->getStorage('supplier')->load($supplierId);
    }
    return $supplier;
  }

  /**
   * Gets TrainClass.
   *
   * @param \Drupal\train_base\Entity\Supplier $supplier
   * @param string $trainClassCode
   * @return \Drupal\train_base\Entity\TrainClass|null
   */
  protected function getTrainClass(Supplier $supplier, $trainClassCode) {
    $trainClass = null;
    $query = \Drupal::entityQuery('train_class');
    $query->condition('supplier', $supplier->id());
    $query->condition('code', $trainClassCode);
    $query->condition('status', 1);
    $query->range(0, 1);
    $trainClassId = $query->execute();
    if ($trainClassId) {
      $trainClassId = reset($trainClassId);
      $trainClass = \Drupal::service('entity_type.manager')->getStorage('train_class')->load($trainClassId);
    }
    return $trainClass;
  }

  /**
   * Gets all children of the Station or itself if has not children.
   *
   * @param \Drupal\train_base\Entity\Station $parent_station
   * @return array|int
   */
  protected function getStationChildren($parent_station) {
    $query = \Drupal::entityQuery('station');
    $query->condition('status', 1);
    $query->condition('parent_station', $parent_station->id());
    $entity_ids = $query->execute();
    if (empty($entity_ids)) {
      $entity_ids[] = $parent_station->id();
    }

    return $entity_ids;
  }

  /**
   * @param \Drupal\Core\Datetime\DrupalDateTime $departure_date
   * @return integer
   */
  protected function getDaysNumberBeforeDeparture(DrupalDateTime $departure_date) {
    $departure_station_timezone = $departure_date->getTimezone();
    $today = DrupalDateTime::createFromtimestamp(time());
    $today->setTimeZone($departure_station_timezone);
    $today->setTime(0, 0);
    $days_before_departure = $departure_date->diff($today)->days;

    return $days_before_departure;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // @todo Implement
    return array();
  }

  /**
   * Sort CoachClassInfoHolder by its price.
   *
   * @param \Drupal\train_provider\CoachClassInfoHolder[] $coachClasses
   */
  protected function sortCoachClassInfoHolders(&$coachClasses) {
    usort($coachClasses, array($this, 'cmpSortCoachClassInfoHolders'));
  }

  /**
   * @param \Drupal\train_provider\CoachClassInfoHolder $coachClass1InfoHolder
   * @param \Drupal\train_provider\CoachClassInfoHolder $coachClass2InfoHolder
   * @return int
   */
  private static function cmpSortCoachClassInfoHolders($coachClass1InfoHolder, $coachClass2InfoHolder) {
    $coachClass1PriceNumber = $coachClass1InfoHolder->getPrice()->getNumber();
    $coachClass2PriceNumber = $coachClass2InfoHolder->getPrice()->getNumber();
    if ($coachClass1PriceNumber == $coachClass2PriceNumber) {
      return 0;
    }
    else if ($coachClass1PriceNumber > $coachClass2PriceNumber) {
      return -1;
    }
    else {
      return 1;
    }
  }

  /**
   * @param \Drupal\train_base\Entity\Supplier $supplier
   * @param string $seatTypeCode
   * @return \Drupal\train_base\Entity\SeatType|null
   */
  protected function getSeatType($supplier, $seatTypeCode) {
    $seatType = null;
    $query = \Drupal::entityQuery('seat_type');
    $query->condition('supplier', $supplier->id());
    $query->condition('code', $seatTypeCode);
    $query->condition('status', 1);
    $query->range(0, 1);
    $seatTypeId = $query->execute();
    if ($seatTypeId) {
      $seatTypeId = reset($seatTypeId);
      $seatType = \Drupal::service('entity_type.manager')->getStorage('seat_type')->load($seatTypeId);
    }
    return $seatType;
  }

  /**
   * @param \Drupal\train_base\Entity\Supplier $supplier
   * @param string $carServiceCode
   * @return \Drupal\train_base\Entity\CarService
   */
  protected function getCarService($supplier, $carServiceCode) {
    $carService = null;
    /** @var \Drupal\Core\Entity\EntityStorageInterface $carServiceStorage */
    $carServiceStorage = \Drupal::service('entity_type.manager')->getStorage('car_service');
    $query = $carServiceStorage->getQuery();
    $query->condition('supplier_mapping.target_id', $supplier->id());
    $query->condition('supplier_mapping.code', $carServiceCode);
    $query->condition('status', 1);
    $query->range(0, 1);
    $carServiceId = $query->execute();
    if ($carServiceId) {
      $carServiceId = reset($carServiceId);
      /** @var \Drupal\train_base\Entity\CarService $carService */
      $carService = $carServiceStorage->load($carServiceId);
    }
    return $carService;
  }

  /**
   * {@inheritdoc}
   */
  protected function isLive() {
    if (isset($this->configuration['live']) && $this->configuration['live'] == true) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * Return code form parent station, or codes from children stations.
   *
   * @param $leg
   * @return array
   */
  protected function prepareStations($leg) {
    $departureStationCodes = $arrivalStationCodes = [];

    $parentDepartureStation = $this->getDepartureStation($leg);
    $departureStationCode = $parentDepartureStation->getStationCodeBySupplierCode(static::$supplierCode);
    if ($departureStationCode) {
      $departureStationCodes[] = $departureStationCode;
    }
    else {
      $departureStations = $this->entityTypeManager->getStorage('station')->loadMultiple($this->getStationChildren($parentDepartureStation));
      /** @var \Drupal\train_base\Entity\Station $departureStation */
      foreach ($departureStations as $departureStation) {
        $departureStationCode = $departureStation->getStationCodeBySupplierCode(static::$supplierCode);
        if ($departureStationCode) {
          $departureStationCodes[] = $departureStationCode;
        }
      }
    }

    $parentArrivalStation = $this->getArrivalStation($leg);
    $arrivalStationCode = $parentArrivalStation->getStationCodeBySupplierCode(static::$supplierCode);
    if ($arrivalStationCode) {
      $arrivalStationCodes[] = $arrivalStationCode;
    }
    else {
      $arrivalStations = $this->entityTypeManager->getStorage('station')->loadMultiple($this->getStationChildren($parentArrivalStation));
      /** @var \Drupal\train_base\Entity\Station $arrivalStation */
      foreach ($arrivalStations as $arrivalStation) {
        $arrivalStationCode = $arrivalStation->getStationCodeBySupplierCode(static::$supplierCode);
        if ($arrivalStationCode) {
          $arrivalStationCodes[] = $arrivalStationCode;
        }
      }
    }

    return ['departure' => $departureStationCodes, 'arrival' => $arrivalStationCodes];
  }
}
