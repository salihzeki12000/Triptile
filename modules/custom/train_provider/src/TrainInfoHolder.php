<?php
/**
 * @file
 * Provides Drupal\train_provider\TrainInfoHolder
 */

namespace Drupal\train_provider;

use Drupal\master\InfoHolderBase;
use Drupal\train_base\Entity\TrainClassInterface;
use Drupal\train_base\Entity\Station;
use Drupal\train_base\Entity\TrainInterface;
use Drupal\Core\Datetime\DrupalDateTime;

class TrainInfoHolder extends InfoHolderBase {

  /**
   * @var \Drupal\train_base\Entity\Train
   *
   */
  protected $train;

  /**
   * @var \Drupal\train_base\Entity\Supplier
   *
   */
  protected $supplier;

  /**
   * @var \Drupal\train_base\Entity\Station
   */
  protected $departure_station;

  /**
   * @var \Drupal\train_base\Entity\Station
   */
  protected $change_station;

  /**
   * @var \Drupal\train_base\Entity\Station
   */
  protected $arrival_station;

  /**
   * @var int
   */
  protected $departure_time;

  /**
   * @var int
   */
  protected $running_time;

  /**
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $departureDateTime;

  /**
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $arrivalDateTime;

  /**
   * @var \Drupal\train_provider\CoachClassInfoHolder[]
   */
  protected $coach_classes;

  /**
   * @var string
   */
  protected $train_number;

  /**
   * @var string
   */
  protected $train_name;

  /**
   * @var \Drupal\train_base\Entity\TrainClass
   */
  protected $train_class;

  /**
   * @var float
   */
  protected $tpRating;

  /**
   * @var float
   */
  protected $internalRating;

  /**
   * @var float
   */
  protected $averageRating;

  /**
   * @var integer
   */
  protected $countOfReviews;

  /**
   * @var string
   */
  protected $message;

  /**
   * @var boolean
   */
  protected $isEticketAvailable;

  /**
   * @var DrupalDateTime
   */
  protected $ticketIssueDate;

  /**
   * Gets the train's entity reference for this train.
   *
   * @return \Drupal\train_base\Entity\TrainInterface
   *   The train's entity reference for this train.
   */
  public function getTrain() {
    if ($this->train) {
      return $this->train;
    }

    return null;
  }

  /**
   * Sets the train's entity reference for this train.
   *
   * @param \Drupal\train_base\Entity\TrainInterface
   *   The train's entity reference for this train.
   */
  public function setTrain(TrainInterface $train) {
    $this->train = $train;
    $this->setTrainNumber($train->getNumber())
      ->setTrainName($train->getName())
      ->setTrainClass($train->getTrainClass())
      ->setSupplier($train->getSupplier())
      ->setEticketAvailable($train->isEticketAvailable());
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
   * Gets the change station's entity reference for this route.
   *
   * @return \Drupal\train_base\Entity\Station
   *   The departure station's entity reference for this route.
   */
  public function getChangeStation() {
    return $this->change_station;
  }

  /**
   * Sets the change station's entity reference for this route.
   *
   * @param \Drupal\train_base\Entity\Station
   *   The departure station's entity reference for this route.
   */
  public function setChangeStation(Station $change_station) {
    $this->change_station = $change_station;
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
   * Sets the arrival station's entity reference for this train.
   *
   * @param \Drupal\train_base\Entity\Station
   *   The arrival station's entity reference.
   */
  public function setArrivalStation(Station $arrival_station) {
    $this->arrival_station = $arrival_station;
  }

  /**
   * Gets the departure time for this train.
   *
   * @return int
   *   The departure time.
   */
  public function getDepartureTime() {
    return $this->departure_time;
  }

  /**
   * Sets the departure time for this train.
   *
   * @param int $departure_time
   *   The departure time.
   */
  public function setDepartureTime(int $departure_time) {
    $this->departure_time = $departure_time;
  }

  /**
   * Gets the running time for this train.
   *
   * @return int
   *   The running time.
   */
  public function getRunningTime() {
    return $this->running_time;
  }

  /**
   * Sets the running time for this train.
   *
   * @param int $running_time
   *   The running time.
   */
  public function setRunningTime(int $running_time) {
    $this->running_time = $running_time;
  }

  /**
   * Gets the departure datetime for this train.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The departure datetime.
   */
  public function getDepartureDateTime() {
    return $this->departureDateTime;
  }

  /**
   * Sets the departure datetime for this train.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime
   *   The departure datetime.
   */
  public function setDepartureDateTime(DrupalDateTime $departure_date_time) {
    $this->departureDateTime = $departure_date_time;
  }

  /**
   * Gets the arrival datetime for this train.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The arrival datetime.
   */
  public function getArrivalDateTime() {
    return $this->arrivalDateTime;
  }

  /**
   * Sets the arrival datetime for this train.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime
   *   The arrival datetime.
   */
  public function setArrivalDateTime(DrupalDateTime $arrival_date_time) {
    $this->arrivalDateTime = $arrival_date_time;
  }

  /**
   * Gets the array of coach class references for this train.
   *
   * @return \Drupal\train_provider\CoachClassInfoHolder[]
   *   Coach class references for this train.
   */
  public function getCoachClasses() {
    return $this->coach_classes;
  }

  /**
   * Sets the array of coach class references for this train.
   *
   * @param array $coach_classes
   *  Coach class references for this train.
   */
  public function setCoachClasses(array $coach_classes) {
    $this->coach_classes = $coach_classes;
  }

  /**
   * Gets a coach class by its offset.
   *
   * @param int $offset
   * @return \Drupal\train_provider\CoachClassInfoHolder|null
   */
  public function getCoachClass($offset = 0) {
    return isset($this->coach_classes[$offset]) ? $this->coach_classes[$offset] : NULL;
  }

  /**
   * @return string
   */
  public function getTrainNumber() {
    return $this->train_number;
  }

  /**
   * @param string $train_number
   * @return static
   */
  public function setTrainNumber($train_number) {
    $this->train_number = $train_number;
    return $this;
  }

  /**
   * @return string
   */
  public function getTrainName() {
    return $this->train_name;
  }

  /**
   * @param string $train_name
   * @return static
   */
  public function setTrainName($train_name) {
    $this->train_name = $train_name;
    return $this;
  }

  /**
   * @return \Drupal\train_base\Entity\TrainClass
   */
  public function getTrainClass() {
    return $this->train_class;
  }

  /**
   * @param \Drupal\train_base\Entity\TrainClassInterface $train_class
   * @return static
   */
  public function setTrainClass(TrainClassInterface $train_class) {
    $this->train_class = $train_class;
    return $this;
  }

  /**
   * Gets supplier entity for the train.
   *
   * @return \Drupal\train_base\Entity\Supplier
   */
  public function getSupplier() {
    return $this->supplier;
  }

  /**
   * Sets supplier entity for the train.
   *
   * @param \Drupal\train_base\Entity\Supplier $supplier
   * @return static
   */
  public function setSupplier($supplier) {
    $this->supplier = $supplier;
    return $this;
  }

  /**
   * Gets train's Trust Pilot rating.
   *
   * @return float
   */
  public function getTPRating() {
    if ($this->train) {
      return (float) $this->train->getTPRating();
    }
    return null;
  }

  /**
   * Sets train's Trust Pilot rating.
   *
   * @param  float $rating
   * @return static
   */
  public function setTPRating($rating) {
    $this->tpRating = $rating;
    return $this;
  }

  /**
   * Gets train's Internal rating.
   *
   * @return float
   */
  public function getInternalRating() {
    if ($this->train) {
      return (float) $this->train->getTPRating();
    }
    return null;
  }

  /**
   * Gets train's Average rating (based on TP and internal rating).
   *
   * @return float
   */
  public function getAverageRating() {
    if ($this->train) {
      return (float) $this->train->getAverageRating();
    }
    return null;
  }

  /**
   * Gets train's count of reviews.
   *
   * @return integer
   */
  public function getCountOfReviews() {
    if ($this->train) {
      return $this->train->getCountOfReviews();
    }
    return null;
  }

  /**
   * Gets Train message.
   *
   * @return string
   */
  public function getMessage() {
    if ($this->train) {
      return $this->train->getMessage();
    }
    return null;
  }

  /**
   * Set E-ticket available indicator.
   *
   * @param $value
   */
  public function setEticketAvailable($value) {
    $this->isEticketAvailable = $value;
  }

  /**
   * Return true if E-ticket available for this train.
   *
   * @return bool
   */
  public function isEticketAvailable() {
    return (bool) $this->isEticketAvailable;
  }

  /**
   * Gets train's ticketIssueDate.
   *
   * @return DrupalDateTime|null
   */
  public function getTicketIssueDate() {
    return $this->ticketIssueDate;
  }

  /**
   * Sets train's ticketIssueDate.
   *
   * @param DrupalDateTime $date
   * @return static
   */
  public function setTicketIssueDate(DrupalDateTime $date) {
    $this->ticketIssueDate = $date;

    return $this;
  }

}
