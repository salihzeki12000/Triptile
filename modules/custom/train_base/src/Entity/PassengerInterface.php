<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Passenger entities.
 *
 * @ingroup train_base
 */
interface PassengerInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Passenger First name.
   *
   * @return string
   */
  public function getFirstName();

  /**
   * Sets passenger first name.
   *
   * @param string $first_name
   * @return static
   */
  public function setFirstName($first_name);

  /**
   * Gets the Passenger Last name.
   *
   * @return string
   */
  public function getLastName();

  /**
   * Sets passenger last name.
   *
   * @param string $last_name
   * @return static
   */
  public function setLastName($last_name);

  /**
   * sets the Passenger Owner.
   *
   * @param $user
   * @return static
   */
  public function setOwner($user);

  /**
   * Gets the Passenger Name.
   *
   * @return string
   */
  public function getName();

  /**
   * Gets the Passenger gender.
   *
   * @return string
   */
  public function getGender();

  /**
   * Sets passenger gender.
   *
   * @param string $gender
   * @return static
   */
  public function setGender($gender);

  /**
   * Gets the Passenger ID number.
   *
   * @return string
   */
  public function getIdNumber();

  /**
   * Sets passenger id number.
   *
   * @param string $id_number
   * @return static
   */
  public function setIdNumber($id_number);

  /**
   * Gets the Passenger DOB.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  public function getDob();

  /**
   * Sets passenger date of birth.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $dob
   * @return static
   */
  public function setDob(DrupalDateTime $dob);

  /**
   * Gets the Passenger creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Passenger.
   */
  public function getCreatedTime();

  /**
   * Sets the Passenger creation timestamp.
   *
   * @param int $timestamp
   *   The Passenger creation timestamp.
   *
   * @return \Drupal\train_base\Entity\PassengerInterface
   *   The called Passenger entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the passenger citizenship.
   *
   * @return mixed
   */
  public function getCitizenship();

  /**
   * Sets passenger citizenship.
   *
   * @param string $country_code
   *  2-letter country code.
   * @return static
   */
  public function setCitizenship($country_code);

  /**
   * Gets all tickets user attached to.
   *
   * @return \Drupal\train_base\Entity\TrainTicket
   */
  public function getTicket();

  /**
   * Gets passenger title.
   *
   * @return string
   */
  public function getTitle();

  /**
   * Sets passenger title.
   *
   * @param string $title
   * @return static
   */
  public function setTitle($title);

}
