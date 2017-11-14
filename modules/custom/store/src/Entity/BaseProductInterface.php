<?php

namespace Drupal\store\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\train_base\Entity\CoachClass;
use Drupal\store\Price;
use Drupal\train_base\Entity\SeatType;

/**
 * Provides an interface for defining Base product entities.
 *
 * @ingroup store
 */
interface BaseProductInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Base product type.
   *
   * @return string
   *   The Base product type.
   */
  public function getType();

  /**
   * Gets the Base product name.
   *
   * @return string
   *   Name of the Base product.
   */
  public function getName();

  /**
   * Sets the Base product name.
   *
   * @param string $name
   *   The Base product name.
   *
   * @return \Drupal\store\Entity\BaseProductInterface
   *   The called Base product entity.
   */
  public function setName($name);

  /**
   * Gets the field form name for the service.
   *
   * @return string
   */
  public function getFieldForm();

  /**
   * Gets the Base product creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Base product.
   */
  public function getCreatedTime();

  /**
   * Sets the Base product creation timestamp.
   *
   * @param int $timestamp
   *   The Base product creation timestamp.
   *
   * @return \Drupal\store\Entity\BaseProductInterface
   *   The called Base product entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets supplier status.
   *
   * @return bool
   */
  public function isEnabled();

  /**
   * Gets the price as an object of type \Drupal\store\Price.
   *
   * @return \Drupal\store\Price
   */
  public function getPrice();

  /**
   * Sets product price value.
   *
   * @param \Drupal\store\Price $price
   * @return static
   */
  public function setPrice(Price $price);

  /**
   * Gets product price currency.
   *
   * @return string
   */
  public function getCurrency();

  /**
   * Gets product price title.
   *
   * @return string
   */
  public function getPriceTitle();

  /**
   * Gets Coach class of the product.
   *
   * @return \Drupal\train_base\Entity\CoachClass
   */
  public function getCoachClass();

  /**
   * Sets Coach class of the product.
   *
   * @param \Drupal\train_base\Entity\CoachClass $coach_class
   * @return static
   */
  public function setCoachClass(CoachClass $coach_class);

  /**
   * Gets SeatType of the product.
   *
   * @return \Drupal\train_base\Entity\SeatType
   */
  public function getSeatType();

  /**
   * Sets SeatType of the product.
   *
   * @param \Drupal\train_base\Entity\SeatType $seat_type
   * @return static
   */
  public function setSeatType(SeatType $seat_type);

  /**
   * Gets product description.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Gets minimal departure window if set.
   *
   * @return int|null
   */
  public function getMinimalDepartureWindow();

  /**
   * Gets the product weight.
   *
   * @return int
   */
  public function getWeight();

  /**
   * Checks if product is default.
   * If field 'default' doesn't exist it returns null.
   *
   * @return bool|null
   */
  public function isDefault();

  /**
   * Gets date when the product becomes available.
   * If field 'available_from' doesn't exist it returns null.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   */
  public function getAvailableFrom();

  /**
   * Gets date when the product becomes unavailable.
   * If field 'available_until' doesn't exist it returns null.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   */
  public function getAvailableUntil();

  /**
   * Sets the date when the product becomes available.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime|string $date
   * @return static
   */
  public function setAvailableFrom(DrupalDateTime $date);

  /**
   * Sets the date when the product becomes unavailable.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime|string $date
   * @return static
   */
  public function setAvailableUntil(DrupalDateTime $date);

  /**
   * Get maximal quantity.
   *
   * @return int|null
   */
  public function getMaxQuantity();

  /**
   * Sets the published status of a BaseProduct.
   *
   * @param bool $published
   *   TRUE to set this BaseProduct to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\store\Entity\BaseProductInterface
   *   The called BaseProduct entity.
   */
  public function setPublished($published);

}
