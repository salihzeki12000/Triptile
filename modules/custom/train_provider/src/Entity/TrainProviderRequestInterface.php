<?php

namespace Drupal\train_provider\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Booking stat entities.
 *
 * @ingroup train_provider
 */
interface TrainProviderRequestInterface extends  ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Booking stat creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Booking stat.
   */
  public function getCreatedTime();

  /**
   * Sets the Booking stat creation timestamp.
   *
   * @param int $timestamp
   *   The Booking stat creation timestamp.
   *
   * @return static
   *   The called Booking stat entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets departure station.
   *
   * @return \Drupal\train_base\Entity\StationInterface
   */
  public function getDepartureStation();

  /**
   * Gets arrival station.
   *
   * @return \Drupal\train_base\Entity\StationInterface
   */
  public function getArrivalStation();

  /**
   * Gets count of success bookings.
   *
   * @return int
   */
  public function getSuccessBookingCount();

  /**
   * Increases count of success bookings.
   *
   * @param int $i
   * @return static
   */
  public function incrementSuccessBookingCount($i = 1);

  /**
   * Gets failed booking count.
   *
   * @return int
   */
  public function getFailedBookingCount();

  /**
   * Increases count of failed bookings.
   *
   * @param int $i
   * @return static
   */
  public function incrementFailedBookingCount($i = 1);


}
