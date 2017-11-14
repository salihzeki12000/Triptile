<?php
/**
 * @file
 * Provides Drupal\train_provider\CoachClassInfoHolder
 */

namespace Drupal\train_provider;

use Drupal\master\InfoHolderBase;
use Drupal\store\Price;
use Drupal\store\Entity\BaseProduct;
use Drupal\train_base\Entity\CoachClass;
use Drupal\train_base\Entity\SeatType;

/**
 * Class CoachClassInfoHolder
 *
 * @package Drupal\train_provider
 *
 * @todo Rewrite it to use TypedData API
 */
class CoachClassInfoHolder extends InfoHolderBase {

  /**
   * {@inheritdoc}
   */
  protected static $arraysOfEntities = array('car_services');

  /**
   * @var \Drupal\store\Entity\BaseProduct
   */
  protected $product;

  /**
   * @var \Drupal\train_base\Entity\CoachClass
   */
  protected $coach_class;

  /**
   * @var \Drupal\train_base\Entity\SeatType
   */
  protected $seat_type;

  /**
   * @var \Drupal\train_base\Entity\CarService[]
   */
  protected $car_services = [];

  /**
   * @var \Drupal\store\Price
   */
  protected $price;

  /**
   * @var \Drupal\store\Price
   */
  protected $originalPrice;

  /**
   * @var int
   */
  protected $countOfAvailableTickets;

  /**
   * The ID of the CoachClassInfoHolder owner.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * Gets the product for this coach class.
   *
   * @return \Drupal\store\Entity\BaseProduct
   */
  public function getProduct() {
    return $this->product;
  }

  /**
   * Sets the product for this coach class.
   *
   * @param \Drupal\store\Entity\BaseProduct
   */
  public function setProduct(BaseProduct $product) {
    $this->product = $product;
  }

  /**
   * Gets the coach classes entity reference for this coach class.
   *
   * @return \Drupal\train_base\Entity\CoachClass
   *   The coach classes entity reference for this coach class.
   */
  public function getCoachClass() {
    return $this->coach_class;
  }

  /**
   * Sets the coach classes entity reference for this coach class.
   *
   * @param \Drupal\train_base\Entity\CoachClass
   *   The coach classes entity reference for this coach class.
   */
  public function setCoachClass(CoachClass $coach_class) {
    $this->coach_class = $coach_class;
  }

  /**
   * Gets the seat type's entity reference for this coach class.
   *
   * @return \Drupal\train_base\Entity\SeatType
   *   The seat type's entity reference for this coach class.
   */
  public function getSeatType() {
    return $this->seat_type;
  }

  /**
   * Sets the seat type's entity reference for this coach class.
   *
   * @param \Drupal\train_base\Entity\SeatType
   *   The seat type's entity reference for this coach class.
   */
  public function setSeatType(SeatType $seat_type) {
    $this->seat_type = $seat_type;
  }

  /**
   * Gets the array of car services references for this coach class.
   *
   * @return \Drupal\train_base\Entity\CarService[]
   *   The car services references for this coach class.
   */
  public function getCarServices() {
    return $this->car_services;
  }

  /**
   * Sets the array of car services references for this coach class.
   *
   * @param array $car_services
   *   The car services references for this coach class.
   */
  public function setCarServices(array $car_services) {
    if ($car_services) {
      $this->car_services = $car_services;
    }
  }

  /**
   * Gets the countOfAvailableTickets of this coach class.
   *
   * @return int
   *   The countOfAvailableTickets of this coach class.
   */
  public function getCountOfAvailableTickets() {
    return $this->countOfAvailableTickets;
  }

  /**
   * Adds some tickets to the current value.
   *
   * @param $countOfAvailableTickets
   * @return static
   */
  public function addCountOfAvailableTickets($countOfAvailableTickets) {
    $this->countOfAvailableTickets += $countOfAvailableTickets;

    return $this;
  }

  /**
   * Sets the countOfAvailableTickets of this coach class.

   * @param $countOfAvailableTickets
   * @return static
   */
  public function setCountOfAvailableTickets($countOfAvailableTickets) {
    $this->countOfAvailableTickets = $countOfAvailableTickets;

    return $this;
  }

  /**
   * Gets the ticket price.
   *
   * @return \Drupal\store\Price
   */
  public function getPrice() {
    return $this->price;
  }

  /**
   * Sets the ticket price.
   *
   * @param \Drupal\store\Price $price
   * @return static
   */
  public function setPrice(Price $price) {
    $this->price = $price;
    return $this;
  }

  /**
   * Gets the ticket original price.
   *
   * @return \Drupal\store\Price
   */
  public function getOriginalPrice() {
    return $this->originalPrice;
  }

  /**
   * Sets the ticket original price.
   * @param \Drupal\store\Price $price
   * @return static
   */
  public function setOriginalPrice(Price $price) {
    $this->originalPrice = $price;
    return $this;
  }

  /**
   * Get plugin ID of the CoachClassInfoHolder.
   *
   * @return string
   */
  public function getPluginId() {
    return $this->pluginId;
  }

  /**
   * Set plugin ID of the CoachClassInfoHolder.
   *
   * @param $pluginId
   * @return $this
   */
  public function setPluginId($pluginId) {
    $this->pluginId = $pluginId;
    return $this;
  }

}