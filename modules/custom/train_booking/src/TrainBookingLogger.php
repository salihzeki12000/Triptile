<?php

namespace Drupal\train_booking;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Datetime\Time;
use Drupal\Core\Language\LanguageManager;
use Drupal\lead\UserMetaData;
use Drupal\master\MasterMaxMind;
use Drupal\store\DefaultCurrency;
use Drupal\store\Entity\StoreOrder;

/**
 * Class TrainBookingLogger.
 *
 * @package Drupal\train_booking
 */
class TrainBookingLogger {

  const TABLE_NAME_LOGGER = 'train_booking_logger';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The date time.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $dateTime;

  /**
   * The Max Mind.
   *
   * @var \Drupal\master\MasterMaxMind
   */
  protected $maxMind;

  /**
   * The Default Currency service.
   *
   * @var \Drupal\store\DefaultCurrency
   */
  protected $defaultCurrency;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * @var \Drupal\lead\UserMetaData
   */
  protected $userMetaData;

  /**
   * TrainBookingLogger constructor.
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Component\Datetime\Time $date_time
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   * @param \Drupal\master\MasterMaxMind $max_mind
   * @param \Drupal\store\DefaultCurrency $default_currency
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   * @param \Drupal\lead\UserMetaData $user_meta_data
   */
  public function __construct(
    Connection $database, EntityTypeManagerInterface $entity_type_manager,
    Time $date_time, LanguageManager $language_manager, MasterMaxMind $max_mind,
    DefaultCurrency $default_currency, DateFormatterInterface $date_formatter,
    UserMetaData $user_meta_data
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateTime = $date_time;
    $this->languageManager = $language_manager;
    $this->maxMind = $max_mind;
    $this->defaultCurrency = $default_currency;
    $this->dateFormatter = $date_formatter;
    $this->userMetaData = $user_meta_data;
  }

  /**
   * Add new record in table train_booking_logger.
   *
   * @param array $data
   *
   * @return bool|\Drupal\Core\Database\StatementInterface|int|null
   */
  protected function add($data) {
    if (empty($data)) {
      return FALSE;
    }
    $fields = [];
    foreach ($data as $key => $value) {
      $fields[$key] = $value;
    }
    try {
      return $this
        ->database
        ->insert($this::TABLE_NAME_LOGGER)
        ->fields($fields)
        ->execute();
    }
    catch (\Exception $e) {
      watchdog_exception('train_booking', $e);
    }
    return false;
  }

  /**
   * Update record in table train_booking_logger.
   */
  protected function update($session_id, $data) {
    if (empty($data) || empty($session_id)) {
      return FALSE;
    }
    $fields = [];
    foreach ($data as $key => $value) {
      $fields[$key] = $value;
    }
    try {
      return $this
        ->database
        ->update($this::TABLE_NAME_LOGGER)
        ->fields($fields)
        ->condition('session_id', $session_id)
        ->execute();
    }
    catch (\Exception $e) {
      watchdog_exception('train_booking', $e);
    }
    return false;
  }

  /**
   * Get data.
   *
   * @param array $conditions
   * @param int $limit
   *
   * @return
   */
  public function getData($conditions = [], $limit = 0) {
    $query = $this
      ->database
      ->select($this::TABLE_NAME_LOGGER)
      ->fields($this::TABLE_NAME_LOGGER);
    if (!empty($conditions)) {
      foreach ($conditions as $condition) {
        if (empty($condition['operator'])) {
          $condition['operator'] = '=';
        }
        $query->condition($condition['field'], $condition['value'], $condition['operator']);
      }
    }
    if (!empty($limit)) {
      $query->range(0, $limit);
    }
    return $query->execute()->fetchAll();
  }

  /**
   * Add log for Search form.
   *
   * @param string $session_id
   * @param array $store_data
   * @param $search_result
   */
  public function logSearchForm($session_id, $store_data, $search_result) {
    /** @var \Drupal\train_base\Entity\Station $from */
    $from = $this
      ->entityTypeManager
      ->getStorage('station')
      ->load($store_data['legs'][1]['departure_station']);
    $from = $from->getParentStation() ?: $from;

    /** @var \Drupal\train_base\Entity\Station $to */
    $to = $this
      ->entityTypeManager
      ->getStorage('station')
      ->load($store_data['legs'][1]['arrival_station']);
    $to = $to->getParentStation() ?: $to;

    $minPrice = 0;
    /** @var \Drupal\train_provider\RouteInfoHolder $routeInfoHolder */
    foreach ($search_result as $routeInfoHolder) {
      $prices = [];
      foreach ($routeInfoHolder->getTrains() as $train) {
        foreach ($train->getCoachClasses() as $coachClass) {
          $prices[] = (int) $coachClass->getPrice()->convert('USD')->getNumber();
        }
      }
      $minPrice += min($prices);
    }

    $departure_date = $store_data['legs'][1]['departure_date'];
    $current_date = new DrupalDateTime();
    $request_time = $this->dateTime->getRequestTime();
    $data_log = [
      'session_id' => $session_id,
      'search_datetime' => $this->dateFormatter->format($request_time, 'custom', 'Y-m-d H:i:s', 'UTC'),
      'ga_client_id' => $this->userMetaData->getGaClientId(),
      'departure' => !empty($from) ? $from->get('name')->value : '',
      'arrival' => !empty($to) ? $to->get('name')->value : '',
      'depth' => $current_date->diff($departure_date)->days,
      'roundtrip' => (int) $store_data['round_trip'],
      'multileg' => (int) $store_data['complex_trip'],
      'pax' => count($store_data['pax']),
      'child' => (int) $store_data['children'],
      'language' => $this->languageManager->getCurrentLanguage()->getId(),
      'country' => $this->maxMind->getCountry(),
      'currency' => $this->defaultCurrency->getUserCurrency(),
      'min_price' => $minPrice,
      'last_step' => 1,
    ];
    $this->add($data_log);
  }

  /**
   * Add log for Timetable form.
   *
   * @param string $session_id
   * @param array $timetable_results
   * @param \Drupal\train_provider\RouteInfoHolder[] $route_info_holders
   */
  public function logTimetableForm($session_id, $timetable_results, $route_info_holders) {
    $markup = 0;
    $price = 0;
    $cost = 0;
    $departure_times = [];
    $coach_class = '';
    $train_number = '';
    $supplier = '';
    $departure_timezone = '';
    foreach ($timetable_results as $leg => $timetable_result ) {
      /** @var \Drupal\train_provider\CoachClassInfoHolder $coach_class_info */
      $coach_class_info = $timetable_result['coach_class_info'];
      /** @var \Drupal\train_provider\TrainInfoHolder $train_info */
      $train_info = $timetable_result['train_info'];
      $departure_station = $route_info_holders[$leg]->getDepartureStation();
      $price += $coach_class_info->getPrice()->convert('USD')->getNumber();
      $markup += $coach_class_info->getPrice()->convert('USD')->subtract($coach_class_info->getOriginalPrice())->getNumber();
      $departure_times[] = $train_info->getDepartureDateTime()->getTimestamp();
      $cost += $coach_class_info->getOriginalPrice()->convert('USD')->getNumber();
      if ($leg == 1) {
        $coach_class = $coach_class_info->getCoachClass()->getName();
        $train_number = $train_info->getTrainNumber();
        $supplier = $train_info->getSupplier()->getName();
        $departure_timezone = $departure_station->getTimezone()->getName();
      }
    }
    $departure_time = min($departure_times);
    $data_log = [
      'markup' => $markup,
      'coach_class' => $coach_class,
      'train_number' => $train_number,
      'price' => $price,
      'departure_time' => $this->dateFormatter->format($departure_time, 'custom', 'H:i', $departure_timezone),
      'supplier' => $supplier,
      'cost' => $cost,
    ];
    $this->update($session_id, $data_log);
  }

  /**
   * Add log for Passenger form.
   *
   * @param string $session_id
   * @param array $order_items
   * @param \Drupal\store\Entity\StoreOrder $order
   */
  public function logPassengerForm($session_id, $order_items, StoreOrder $order) {
    $tax = 0;
    $optional = FALSE;
    foreach ($order_items as $orderItem) {
      $orderItem = reset($orderItem);
      if ($orderItem->bundle() == TrainBookingManager::TAX_ORDER_ITEM_TYPE) {
        $tax = $orderItem->getPrice()->convert('USD')->getNumber();
      }
      if ($orderItem->bundle() == TrainBookingManager::OPTIONAL_SERVICE_ORDER_ITEM_TYPE) {
        $optional = TRUE;
      }
    }
    $data_log = [
      'last_step' => 3,
      'tax' => $tax,
      'optional' => (int) $optional,
      'order_total' => $order->getOrderTotal()->convert('USD')->getNumber(),
    ];
    $this->update($session_id, $data_log);
  }

  /**
   * Add log for Payment form.
   *
   * @param string $session_id
   * @param array $payment_result
   */
  public function logPaymentForm($session_id, $payment_result) {
    $data_log = [
      'last_step' => 4,
      'payment_method' => $payment_result['payment_method'],
      'paid_amount' => $payment_result['paid_amount'],
      'order_number' => $payment_result['order_number'],
    ];
    $this->update($session_id, $data_log);
  }

  /**
   * Add log for Payment Status.
   *
   * @param string $session_id
   * @param string $status
   */
  public function logPaymentStatus($session_id, $status) {
    $data_log = [
      'status' => $status,
    ];
    $this->update($session_id, $data_log);
  }

  /**
   * Updates last step in log.
   *
   * @param string $session_id
   * @param int $step
   */
  public function logLastStep($session_id, $step) {
    $data = ['last_step' => $step];
    $this->update($session_id, $data);
  }

}
