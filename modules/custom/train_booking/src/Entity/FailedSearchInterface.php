<?php

namespace Drupal\train_booking\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Failed search entities.
 *
 * @ingroup train_booking
 */
interface FailedSearchInterface extends  ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Failed search departure station.
   *
   * @return \Drupal\train_base\Entity\Station
   */
  public function getDepartureStation();

  /**
   * Sets the Failed departure station.
   *
   * @param \Drupal\train_base\Entity\Station $departure_station
   *
   * @return \Drupal\train_booking\Entity\FailedSearchInterface
   *   The called Failed search entity.
   */
  public function setDepartureStation($departure_station);

  /**
   * Gets the Failed search arrival station.
   *
   * @return \Drupal\train_base\Entity\Station
   */
  public function getArrivalStation();

  /**
   * Sets the Failed search arrival station.
   *
   * @param \Drupal\train_base\Entity\Station $arrival_station
   *
   * @return \Drupal\train_booking\Entity\FailedSearchInterface
   *   The called Failed search entity.
   */
  public function setArrivalStation($arrival_station);

  /**
   * Gets count of failed search for the route.
   *
   * @return int
   */
  public function getCount();

  /**
   * Increment failed search with the route.
   *
   * @param int $i
   * @return \Drupal\train_booking\Entity\FailedSearchInterface The called Failed search entity.
   *   The called Failed search entity.
   */
  public function incrementCount($i = 1);

  /**
   * Gets the Failed search creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Failed search.
   */
  public function getCreatedTime();

  /**
   * Sets the Failed search creation timestamp.
   *
   * @param int $timestamp
   *   The Failed search creation timestamp.
   *
   * @return \Drupal\train_booking\Entity\FailedSearchInterface
   *   The called Failed search entity.
   */
  public function setCreatedTime($timestamp);

}
