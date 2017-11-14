<?php

namespace Drupal\local_train_provider\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\master\Entity\EntityEnabledInterface;

/**
 * Provides an interface for defining Timetable entry entities.
 *
 * @ingroup local_train_provider
 */
interface TimetableEntryInterface extends ContentEntityInterface, EntityChangedInterface, EntityEnabledInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Timetable entry creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Timetable entry.
   */
  public function getCreatedTime();

  /**
   * Sets the Timetable entry creation timestamp.
   *
   * @param int $timestamp
   *   The Timetable entry creation timestamp.
   *
   * @return \Drupal\local_train_provider\Entity\TimetableEntryInterface
   *   The called Timetable entry entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets Train entity of this timetable
   *
   * @return \Drupal\train_base\Entity\Train
   */
  public function getTrain();

  /**
   * Gets Departure station entity of this timetable
   *
   * @return \Drupal\train_base\Entity\Station
   */
  public function getDepartureStation();

  /**
   * Gets Change station entity of this timetable
   *
   * @return \Drupal\train_base\Entity\Station
   */
  public function getChangeStation();

  /**
   * Gets Arrival station entity of this timetable
   *
   * @return \Drupal\train_base\Entity\Station
   */
  public function getArrivalStation();

  /**
   * Gets Departure time of this timetable
   *
   * @return int
   */
  public function getDepartureTime();

  /**
   * Gets Running time of this timetable
   *
   * @return int
   */
  public function getRunningTime();

  /**
   * Gets min departure window of this timetable
   *
   * @return int
   */
  public function getMinDepartureWindow();

  /**
   * Sets min departure window of this timetable
   *
   * @param int $number
   * @return static
   */
  public function setMinDepartureWindow($number);

  /**
   * Gets schedule "every N days" option.
   *
   * @return int | null
   */
  public function getEveryNDays();

  /**
   * Gets schedule "available From" option.
   *
   * @return string
   */
  public function getAvailableFrom();

  /**
   * Gets locked days.
   *
   * @return array
   */
  public function getLockedDay();

  /**
   * Gets array of Products
   *
   * @return \Drupal\store\Entity\BaseProduct[]
   */
  public function getProducts();

  /**
   * Sets the timetable entry status.
   *
   * @param bool $status
   * @return static
   */
  public function setStatus($status);

  /**
   * Gets price updater provider.
   *
   * @return string
   */
  public function getPriceUpdater();

  /**
   * Sets price updater provider.
   *
   * @param $priceUpdater
   * @return static
   */
  public function setPriceUpdater($priceUpdater);

  /**
   * Gets depth for price update request.
   *
   * @return int
   */
  public function getDepthForPriceUpdate();

  /**
   * Sets depth for price update request.
   *
   * @param $depth
   * @return static
   */
  public function setDepthForPriceUpdate($depth);
  /**
   * Gets last price update timestamp.
   *
   * @return int
   */
  public function getLastPriceUpdateTimestamp();

  /**
   * Sets last price update timestamp.
   *
   * @param int $timestamp
   * @return static
   */
  public function setLastPriceUpdateTimestamp($timestamp);

  /**
   * Gets maximal order depth.
   *
   * @return int
   */
  public function getMaxOrderDepth();

}
