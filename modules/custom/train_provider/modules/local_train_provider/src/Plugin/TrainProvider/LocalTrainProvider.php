<?php

namespace Drupal\local_train_provider\Plugin\TrainProvider;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\local_train_provider\Entity\TimetableEntry;
use Drupal\train_provider\TrainProviderBase;
use Drupal\train_provider\TrainInfoHolder;
use Drupal\train_provider\CoachClassInfoHolder;
use Drupal\master\EntityWhereCondition;

/**
 * Provides Local Train Provider.
 *
 * @TrainProvider(
 *   id = "local_train_provider",
 *   label = "Local train provider",
 *   description = "Uses entities of type 'timetable_entry' to provide timetable.",
 *   operations_provider = "Drupal\train_provider\Plugin\TrainProvider\PluginOperationsProvider",
 *   price_updater = false
 * )
 */
class LocalTrainProvider extends TrainProviderBase {

  /**
   * {@inheritdoc}
   */
  public function getTimeTable() {
    $trains = [1 => [], 2 => []];

    foreach ($this->configuration['legs'] as $leg => $legData) {

      // Don't make search if searching is earlier thane today + min_departure_window.
      $min_departure_window = $this->getDaysNumberBeforeDeparture($this->getDepartureDate($leg));
      if ($min_departure_window < $this->getMinDaysBeforeDeparture() || $min_departure_window < $this->getCommonMinDaysBeforeDeparture()) {
        continue;
      }

      // Prepare variables for query conditions.
      $departureStationTimezone = $this->getDepartureDate($leg)->getTimezone();

      // Calculate minimal departure window.
      $days_before_departure = $this->getDaysNumberBeforeDeparture($this->getDepartureDate($leg));

      // Departure date.
      $departure_date = $this->getDepartureDate($leg)->format(DATETIME_DATE_STORAGE_FORMAT);
      $departure_date_ar = getdate(strtotime($departure_date));
      $offset = ($departure_date_ar['wday'] == 0) ? 6 : $departure_date_ar['wday'] - 1;
      $day_of_week_bit = 1 << $offset;

      // Even Days.
      $even_days = (($departure_date_ar['mday'] % 2) == 0) ? 2 : 1;

      // Initialize query.
      $query = $this->entityQuery->get('timetable_entry');
      $query->condition('status', 1);

      // Minimal departure window.
      $query->condition('min_departure_window', $days_before_departure, '<=');

      // Field schedule condition.
      $where_condition = new EntityWhereCondition('AND', $query);
      $where_condition->where(':schedule & :value = :value', [':schedule' => 'schedule.weekdays'], [':value' => $day_of_week_bit]);
      $week_days = $query->orConditionGroup()
        ->condition($where_condition)
        ->notExists('schedule.weekdays');
      $even_days = $query->orConditionGroup()
        ->condition('schedule.even_days', $even_days)
        ->notExists('schedule.even_days');
      $query->condition($week_days);
      $query->condition($even_days);
      $available_from = $query->orConditionGroup()
        ->condition('schedule.available_from', $departure_date, '<=')
        ->notExists('schedule.available_from');
      $query->condition($available_from);
      $available_until = $query->orConditionGroup()
        ->condition('schedule.available_until', $departure_date, '>=')
        ->notExists('schedule.available_until');
      $query->condition($available_until);

      // Departure station (town) has got one or more stations.
      $query->condition('departure_station', $this->getStationChildren($this->getDepartureStation($leg)), 'IN');
      $query->condition('arrival_station', $this->getStationChildren($this->getArrivalStation($leg)), 'IN');

      // Sorting by departure datetime ASC.
      $query->sort('departure_time');

      $entity_ids = $query->execute();

      if ($entity_ids) {
        $entities = $this->entityTypeManager->getStorage('timetable_entry')->loadMultiple($entity_ids);
        $now = DrupalDateTime::createFromtimestamp(time(), $departureStationTimezone);
        $now30 = DrupalDateTime::createFromtimestamp(time() + static::$minimalDepth * 60, $departureStationTimezone);

        /** @var \Drupal\local_train_provider\Entity\TimetableEntry $entity */
        foreach ($entities as $entity) {
          $flag = TRUE;
          $every_n_days = $entity->getEveryNDays();
          $locked_days = $entity->getLockedDay();
          $departureDatetime = DrupalDateTime::createFromTimestamp($this->getDepartureDate($leg)->getTimestamp() + $entity->getDepartureTime(), $departureStationTimezone);
          $diff = $departureDatetime->diff($now);
          $hoursBeforeDeparture = $diff->h;

          if (($now30->getTimestamp() >= $departureDatetime->getTimestamp()) ||
            ($diff->days == 0 && ($hoursBeforeDeparture < $this->getMinHoursBeforeDeparture() ||
            $hoursBeforeDeparture < $this->getCommonMinHoursBeforeDeparture()))) {
            $flag = FALSE;
          }
          if (!empty($every_n_days)) {
            $available_from = strtotime($entity->getAvailableFrom());
            $departure_time = strtotime($departure_date);
            $change = $departure_time - $available_from;
            if ((int) floor($change / (3600 * 24)) % $every_n_days != 0) {
              $flag = FALSE;
            }
          }
          if (!empty($locked_days)) {
            foreach ($locked_days as $locked_day) {
              if ($locked_day['value'] == $departure_date) {
                $flag = FALSE;
              }
            }
          }
          if ($flag) {
            $train = $this->convertTrainInfo($entity, $leg);
            if ($train) {
              $trains[$leg][] = $train;
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
   * @param \Drupal\local_train_provider\Entity\TimetableEntry $entity
   * @param $leg
   * @return \Drupal\train_provider\TrainInfoHolder
   */
  protected function convertTrainInfo(TimetableEntry $entity, $leg) {
    $coachClassInfoHolders = $this->convertCoachClassInfo($entity, $leg);

    // Skipp train without coach classes.
    if (empty($coachClassInfoHolders)) {
      return null;
    }

    $trainInfoHolder = new TrainInfoHolder();
    $train = $entity->getTrain();
    $trainInfoHolder->setTrain($train);
    $trainInfoHolder->setDepartureStation($entity->getDepartureStation());
    if ($change_station = $entity->getChangeStation()) {
      $trainInfoHolder->setChangeStation($change_station);
    }
    $trainInfoHolder->setArrivalStation($entity->getArrivalStation());
    $trainInfoHolder->setDepartureTime($entity->getDepartureTime());
    $trainInfoHolder->setRunningTime($entity->getRunningTime());
    // @TODO: try to set timezone in the createFromTimestamp method. Probably we can escape custom time modification below.
    $departure_datetime = DrupalDateTime::createFromTimestamp($this->getDepartureDate($leg)->getTimestamp() + $entity->getDepartureTime());
    $departure_datetime->setTimezone($entity->getDepartureStation()->getTimezone());

    // Set ticket issue date.
    if ($leg == 1) {
      $orderDepth = $this->getDaysNumberBeforeDeparture($this->getDepartureDate($leg));
      $maxOrderDepth = $entity->getMaxOrderDepth() != null ? $entity->getMaxOrderDepth() : $train->getSupplier()->getMaxOrderDepth();
      $ticketIssueDate = DrupalDateTime::createFromTimestamp(strtotime(date('Y-m-d')));
      if ($maxOrderDepth != null && $orderDepth > $maxOrderDepth + 1) {
        $ticketIssueDate->modify('+' . $orderDepth - $maxOrderDepth . 'days');
      }
      elseif ($orderDepth > 0) {
        $ticketIssueDate->modify('+1days');
      }
      $trainInfoHolder->setTicketIssueDate($ticketIssueDate);
    }

    // Do not disassemble and do not give their children.
    // On March 26 php $this->getDepartureDate($leg is creating with winter difference in time,
    // $departure_datetime is creating with daylight saving. On October 29 it reverse.
    $departure_midnight = $this->getDepartureDate($leg)->format('I');
    $departure_ldt = $departure_datetime->format('I');
    if (!$departure_midnight && $departure_ldt) {
      $departure_datetime->modify('-1 hour');
    }
    elseif ($departure_midnight && !$departure_ldt) {
      $departure_datetime->modify('+1 hour');
    }
    $arrival_datetime = DrupalDateTime::createFromTimestamp($departure_datetime->getTimestamp() + $entity->getRunningTime());
    $arrival_datetime->setTimeZone($entity->getArrivalStation()->getTimezone());

    // Do not disassemble and do not give their children.
    // Same problem on 25 March and 28 October between departure_datetime
    // and arrival_datetime.
    $arrival_ldt = $arrival_datetime->format('I');
    if (!$departure_ldt && $arrival_ldt) {
      $arrival_datetime->modify('-1 hour');
    }
    elseif ($departure_ldt && !$arrival_ldt) {
      $arrival_datetime->modify('+1 hour');
    }
    $trainInfoHolder->setDepartureDateTime($departure_datetime);
    $trainInfoHolder->setArrivalDateTime($arrival_datetime);
    $trainInfoHolder->setCoachClasses($coachClassInfoHolders);

    return $trainInfoHolder;
  }

  /**
   * Gets CoachClassInfoHolders for this train.
   *
   * @param \Drupal\local_train_provider\Entity\TimetableEntry $entity
   * @param $leg
   * @return \Drupal\train_provider\CoachClassInfoHolder[]
   */
  protected function convertCoachClassInfo(TimetableEntry $entity, $leg) {
    $coachClassInfoHolders = [];
    $priceRule = \Drupal::service('store.price_rule');
    $orderDepth = $this->getDaysNumberBeforeDeparture($this->getDepartureDate($leg));
    $currencyCode = \Drupal::service('store.default_currency')->getUserCurrency();
    $train = $entity->getTrain();
    $supplier = $train->getSupplier();
    $pax = $this->configuration['adult_number'] + $this->configuration['child_number'];

    /** @var \Drupal\Store\Entity\BaseProduct $product */
    foreach ($entity->getProducts() as $product) {
      $seatType = $product->getSeatType();
      $coachClass = $product->getCoachClass();

      // Skip products, which minimal departure window doesn't satisfied order depth.
      if ($product->getMinimalDepartureWindow() > $orderDepth) {
        continue;
      }

      // Skip products, which max quantity field multiplied by seat type capacity less than pax.
      if ($maxQuantity = $product->getMaxQuantity()) {
        $capacity = $seatType->getCapacity();
        if ($maxQuantity * $capacity < $pax) {
          continue;
        }
      }
      $price = $product->getPrice();
      $coachClassInfoHolder = new CoachClassInfoHolder();
      $coachClassInfoHolder->setPluginId($this->pluginId);
      $coachClassInfoHolder->setOriginalPrice($price);
      $coachClassInfoHolder->setProduct($product);
      $coachClassInfoHolder->setCoachClass($coachClass);
      $coachClassInfoHolder->setSeatType($seatType);
      $coachClassInfoHolder->setCarServices($coachClass->getCarServices());
      $coachClassInfoHolder->setCountOfAvailableTickets($product->getMaxQuantity());

      // Switch price to user currency.
      $updatedPrice = $price->convert($currencyCode);

      // Implements before display price rules.
      $updatedPrice = $priceRule->updatePrice('before_display', $updatedPrice,
        ['train' => $train->getNumber(), 'supplier' => $supplier->getCode(), 'order_depth' => $orderDepth])['price'];
      $coachClassInfoHolder->setPrice($updatedPrice);
      $coachClassInfoHolders[] = $coachClassInfoHolder;
    }
    $this->sortCoachClassInfoHolders($coachClassInfoHolders);

    return $coachClassInfoHolders;
  }

}