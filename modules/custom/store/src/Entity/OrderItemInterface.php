<?php

namespace Drupal\store\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Order item entities.
 *
 * @ingroup store
 */
interface OrderItemInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Order item type.
   *
   * @return string
   *   The Order item type.
   */
  public function getType();

  /**
   * Sets order reference.
   *
   * @param int $order_id
   * @return static
   */
  public function setOrder($order_id);


  /**
   * Gets the price as an object of type \Drupal\store\Price.
   *
   * @return \Drupal\store\Price
   */
  public function getPrice();

  /**
   * Sets product price value.
   *
   * @param float $price
   * @return static
   */
  public function setPrice($price);

  /**
   * Gets the original price as an object of type \Drupal\store\Price.
   *
   * @return \Drupal\store\Price
   */
  public function getOriginalPrice();

  /**
   * Sets original price value.
   *
   * @param float $price
   * @return static
   */
  public function setOriginalPrice($price);

  /**
   * Gets product price currency.
   *
   * @return string
   */
  public function getCurrency();

  /**
   * Gets the Order item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Order item.
   */
  public function getCreatedTime();

  /**
   * Sets the Order item creation timestamp.
   *
   * @param int $timestamp
   *   The Order item creation timestamp.
   *
   * @return \Drupal\store\Entity\OrderItemInterface
   *   The called Order item entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets leg number of the order item.
   *
   * @return integer
   */
  public function getLegNumber();

  /**
   * Sets leg number of the order item.
   *
   * @param int $leg_number
   * @return static
   */
  public function setLegNumber($leg_number);

  /**
   * Gets translated order item name.
   *
   * @return string
   */
  public function getName();

  /**
   * Gets quantity of booked products.
   *
   * @return int
   */
  public function getQuantity();

  /**
   * Gets booked product if set.
   *
   * @return \Drupal\store\Entity\BaseProduct|null
   */
  public function getProduct();

}
