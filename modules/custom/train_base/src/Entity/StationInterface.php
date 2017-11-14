<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\master\Entity\EntityEnabledInterface;

/**
 * Provides an interface for defining Station entities.
 *
 * @ingroup train_base
 */
interface StationInterface extends ContentEntityInterface, EntityChangedInterface, EntityEnabledInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Station creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Station.
   */
  public function getCreatedTime();

  /**
   * Sets the Station creation timestamp.
   *
   * @param int $timestamp
   *   The Station creation timestamp.
   *
   * @return \Drupal\train_base\Entity\StationInterface
   *   The called Station entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the translated station name.
   *
   * @return string
   */
  public function getName();

  /**
   * Gets the parent station of current station.
   *
   * @return static|null
   */
  public function getParentStation();

  /**
   * Gets the station time zone.
   *
   * @return \DateTimeZone
   */
  public function getTimezone();

  /**
   * Gets the station country code.
   *
   * @return string
   */
  public function getCountryCode();

  /**
   * Get the station city name.
   *
   * @return string
   */
  public function getLocality();

  /**
   * Gets the station address.
   *
   * @return \Drupal\address\Plugin\Field\FieldType\AddressItem
   */
  public function getAddress();

  /**
   * Gets station latitude
   *
   * @return float
   */
  public function getLatitude();

  /**
   * Gets station longitude
   *
   * @return float
   */
  public function getLongitude();

  /**
   * Gets all children of the Station
   *
   * @return \Drupal\train_base\Entity\Station []
   */
  public function getStationChildren();

  /**
   * Returns array of stations with given station set as a parent station
   *
   * @return array
   */
  public function getStationChildrenIds();

  /**
   * Returns array of ids of station and it's children
   * @return array
   */
  public function getStationWithChildrenIds();

  /**
   * Gets the country code of the station.
   *
   * @return string
   */
  public function getCountry();

  /**
   * Gets list of station that forms popular routes.
   *
   * @return \Drupal\train_base\Entity\Station[]
   */
  public function getPopularRoutes();

  /**
   * Get station code by supplier code.
   *
   * @param $supplierCode
   * @return mixed
   */
  public function getStationCodeBySupplierCode($supplierCode);

  /**
   * Get station code by supplier ID.
   *
   * @param $supplierId
   * @return mixed
   */
  public function getStationCodeBySupplierId($supplierId);

}
