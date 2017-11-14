<?php

namespace Drupal\train_booking\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Booking stat entities.
 *
 * @ingroup train_booking
 */
interface BookingStatInterface extends  ContentEntityInterface, EntityChangedInterface {

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
   * @return \Drupal\train_booking\Entity\BookingStatInterface
   *   The called Booking stat entity.
   */
  public function setCreatedTime($timestamp);

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
   * Gets count of one way trips.
   *
   * @return int
   */
  public function getOneWayTripCount();

  /**
   * Increases count of one way trips.
   *
   * @param int $i
   * @return static
   */
  public function incrementOneWayTripCount($i = 1);

  /**
   * Gets count of round trips.
   *
   * @return int
   */
  public function getComplexTripCount();

  /**
   * Increases count of round trips.
   *
   * @param int $i
   * @return static
   */
  public function incrementComplexTripCount($i = 1);

  /**
   * Gets count of requested tickets.
   *
   * @return int
   */
  public function getTicketCount();

  /**
   * Increases count of requested tickets.
   *
   * @param int $i
   * @return static
   */
  public function incrementTicketCount($i = 1);

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
   * Decreases count of failed bookings.
   *
   * @param int $i
   * @return static
   */
  public function decrementFailedBookingCount($i = 1);

}
