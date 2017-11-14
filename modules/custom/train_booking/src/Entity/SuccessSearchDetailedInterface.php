<?php

namespace Drupal\train_booking\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Detailed success search entities.
 *
 * @ingroup train_booking
 */
interface SuccessSearchDetailedInterface extends  ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Detailed success search creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Detailed success search.
   */
  public function getCreatedTime();

  /**
   * Sets the Detailed success search creation timestamp.
   *
   * @param int $timestamp
   *   The Detailed success search creation timestamp.
   *
   * @return \Drupal\train_booking\Entity\SuccessSearchDetailedInterface
   *   The called Detailed success search entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets count of success searches.
   *
   * @return int
   */
  public function getCount();

  /**
   * Increases count of success searches.
   *
   * @param int $i
   * @return static
   */
  public function incrementCount($i = 1);

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
   * Gets count of passenger page loads.
   *
   * @return int
   */
  public function getPassengerPageLoadCount();

  /**
   * Increases count of passenger page loads.
   *
   * @param int $i
   * @return static
   */
  public function incrementPassengerPageLoadCount($i = 1);

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
   * Gets count of payment page loads.
   *
   * @return int
   */
  public function getPaymentPageLoadCount();

  /**
   * Increases count of payment page loads.
   *
   * @param int $i
   * @return static
   */
  public function incrementPaymentPageLoadCount($i = 1);

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
