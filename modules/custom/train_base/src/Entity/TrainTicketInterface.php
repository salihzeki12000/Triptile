<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides an interface for defining Train ticket entities.
 *
 * @ingroup train_base
 */
interface TrainTicketInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Train ticket creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Train ticket.
   */
  public function getCreatedTime();

  /**
   * Sets the Train ticket creation timestamp.
   *
   * @param int $timestamp
   *   The Train ticket creation timestamp.
   *
   * @return \Drupal\train_base\Entity\TrainTicketInterface
   *   The called Train ticket entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets Departure station of the ticket.
   *
   * @return \Drupal\train_base\Entity\Station
   */
  public function getDepartureStation();

  /**
   * Sets Departure station of the ticket.
   *
   * @param \Drupal\train_base\Entity\Station $departure_station
   * @return static
   */
  public function setDepartureStation(Station $departure_station);

  /**
   * Gets Arrival station of the ticket.
   *
   * @return \Drupal\train_base\Entity\Station
   */
  public function getArrivalStation();

  /**
   * Sets Arrival station of the ticket.
   *
   * @param \Drupal\train_base\Entity\Station $departure_station
   * @return static
   */
  public function setArrivalStation(Station $departure_station);

  /**
   * Gets Departure parent station of the ticket.
   *
   * @return \Drupal\train_base\Entity\Station
   */
  public function getDepartureCity();

  /**
   * Gets Arrival parent station of the ticket.
   *
   * @return \Drupal\train_base\Entity\Station
   */
  public function getArrivalCity();

  /**
   * Gets Departure DateTime of the ticket.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  public function getDepartureDateTime();

  /**
   * Sets Departure DateTime of the ticket.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $dateTime
   * @return static
   */
  public function setDepartureDateTime(DrupalDateTime $dateTime);

  /**
   * Gets Arrival DateTime of the ticket.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  public function getArrivalDateTime();

  /**
   * Sets Arrival DateTime of the ticket.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $dateTime
   * @return static
   */
  public function setArrivalDateTime(DrupalDateTime $dateTime);

  /**
   * Is boarding password required on this train.
   *
   * @return bool
   */
  public function isBoardingPassRequired();

  /**
   * Sets the condition of boarding password required on this train.
   *
   * @param bool $boarding_pass_required
   * @return static
   */
  public function setBoardingPassRequired(bool $boarding_pass_required);

  /**
   * Gets leg number of the ticket.
   *
   * @return integer
   */
  public function getLegNumber();

  /**
   * Sets leg number of the ticket.
   *
   * @param int $leg_number
   * @return static
   */
  public function setLegNumber($leg_number);

  /**
   * Gets Train class of the ticket.
   *
   * @return \Drupal\train_base\Entity\TrainClass
   */
  public function getTrainClass();

  /**
   * Sets Train class of the ticket.
   *
   * @param \Drupal\train_base\Entity\TrainClass $train_class
   * @return static
   */
  public function setTrainClass(TrainClass $train_class);

  /**
   * Gets Coach class of the ticket.
   *
   * @return \Drupal\train_base\Entity\CoachClass
   */
  public function getCoachClass();

  /**
   * Sets Coach class of the ticket.
   *
   * @param \Drupal\train_base\Entity\CoachClass $coach_class
   * @return static
   */
  public function setCoachClass(CoachClass $coach_class);

  /**
   * Gets SeatType of the ticket.
   *
   * @return \Drupal\train_base\Entity\SeatType
   */
  public function getSeatType();

  /**
   * Sets SeatType of the ticket.
   *
   * @param \Drupal\train_base\Entity\SeatType $seat_type
   * @return static
   */
  public function setSeatType(SeatType $seat_type);

  /**
   * Gets Coach number of the ticket.
   *
   * @return int
   */
  public function getCoachNumber();

  /**
   * Sets Seat number of the ticket.
   *
   * @param int $coach_number
   * @return static
   */
  public function setCoachNumber(int $coach_number);

  /**
   * Gets Seat number of the ticket.
   *
   * @return int
   */
  public function getSeatNumber();

  /**
   * Sets Seat number of the ticket.
   *
   * @param int $seat_number
   * @return static
   */
  public function setSeatNumber(int $seat_number);

  /**
   * Sets the Train ticket passengers.
   *
   * @param array $pids
   *   The array of passengers IDs.
   *
   * @return static
   */
  public function setPassengers($pids);

  /**
   * Gets the Train ticket passengers.
   *
   * @return \Drupal\train_base\Entity\Passenger[]
   */
  public function getPassengers();

  /**
   * Gets Train number of the ticket.
   *
   * @return string
   */
  public function getTrainNumber();

  /**
   * Sets Train number of the ticket.
   *
   * @param string $train_number
   * @return static
   */
  public function setTrainNumber(string $train_number);

  /**
   * Gets Train name of the ticket.
   *
   * @return string
   */
  public function getTrainName();

  /**
   * Sets Train name of the ticket.
   *
   * @param string $train_name
   * @return static
   */
  public function setTrainName(string $train_name);

  /**
   * Gets array of CarService entities.
   *
   * @return \Drupal\train_base\Entity\CarService[]
   */
  public function getCarServices();

  /**
   * Sets CarService entities to the TrainTicket.
   *
   * @param array $carServices
   * @return static
   */
  public function setCarServices(array $carServices);

  /**
   * Gets order the ticket attached to.
   *
   * @return \Drupal\store\Entity\StoreOrder
   */
  public function getOrder();

  /**
   * Gets the station where client will change train.
   *
   * @return \Drupal\train_base\Entity\Station
   */
  public function getChangeStation();

  /**
   * Sets the station where client will change train.
   *
   * @param \Drupal\train_base\Entity\StationInterface $station
   * @return static
   */
  public function setChangeStation(StationInterface $station);

}
