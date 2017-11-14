<?php
/**
 * @file
 * Provides Drupal\train_provider\RouteInfoHolder
 */

namespace Drupal\train_provider;

use Drupal\master\InfoHolderBase;
use Drupal\train_base\Entity\Station;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Class RouteInfoHolder
 * @package Drupal\train_provider
 */
class RouteInfoHolder extends InfoHolderBase {

  /**
   * @var \Drupal\train_provider\TrainInfoHolder[]
   */
  protected $trains;

  /**
   * @var \Drupal\train_base\Entity\Station
   */
  protected $departure_station;

  /**
   * @var \Drupal\train_base\Entity\Station
   */
  protected $arrival_station;

  /**
   * @var \Drupal\Core\Datetime\DrupalDateTime;
   */
  protected $departure_date;

  /**
   * @var string;
   */
  protected $_departure_date;

  /**
   * Gets the array of trains references for this route.
   *
   * @return \Drupal\train_provider\TrainInfoHolder[]
   *   Available trains references for this route.
   */
  public function getTrains() {
    return $this->trains;
  }

  /**
   * Sets the array of trains references for this route.
   *
   * @param array $trains
   *   Avaible trains references for this route.
   */
  public function setTrains(array $trains) {
    $this->trains = $trains;
  }

  /**
   * Gets the departure station's entity reference for this route.
   *
   * @return \Drupal\train_base\Entity\Station
   *   The departure station's entity reference for this route.
   */
  public function getDepartureStation() {
    return $this->departure_station;
  }

  /**
   * Sets the departure station's entity reference for this route.
   *
   * @param \Drupal\train_base\Entity\Station
   *   The departure station's entity reference for this route.
   */
  public function setDepartureStation(Station $departure_station) {
    $this->departure_station = $departure_station;
  }

  /**
   * Gets the arrival station's entity reference for this route.
   *
   * @return \Drupal\train_base\Entity\Station
   *   The arrival station's entity reference.
   */
  public function getArrivalStation() {
    return $this->arrival_station;
  }

  /**
   * Sets the arrival station's entity reference for this route.
   *
   * @param \Drupal\train_base\Entity\Station
   *   The arrival station's entity reference.
   */
  public function setArrivalStation(Station $arrival_station) {
    $this->arrival_station = $arrival_station;
  }

  /**
   * Gets the departure date for this route.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  public function getDepartureDate() {
    return $this->departure_date;
  }


  /**
   * Sets the departure date for this route.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $departure_date
   */
  public function setDepartureDate(DrupalDateTime $departure_date) {
    $this->departure_date = $departure_date;
  }

  /**
   * Gets a train from the list of trains by its offset.
   *
   * @param int $offset
   * @return \Drupal\train_provider\TrainInfoHolder|null
   */
  public function getTrain($offset = 0) {
    return isset($this->trains[$offset]) ? $this->trains[$offset] : NULL;
  }

  public function __sleep() {
    $this->_departure_date = $this->departure_date->format('c');
    $keys = parent::__sleep();
    unset($keys[array_search('departure_date', $keys)]);
    return $keys;
  }

  public function __wakeup() {
    parent::__wakeup();
    $this->departure_date = new DrupalDateTime($this->_departure_date);
    $this->_departure_date = null;
  }

}
