<?php

namespace Drupal\booking;

use Drupal\rn_user\SessionStore;
use Drupal\store\Entity\StoreOrder;
use Drupal\store\Entity\Invoice;
use Drupal\store\Entity\OrderItem;
use Drupal\user\Entity\User;

/**
 * Interface BookingManagerInterface.
 *
 * @package Drupal\booking
 */
interface BookingManagerInterface {

  /**
   * Sets session store containing booking data.
   *
   * @param \Drupal\rn_user\SessionStore $store
   * @return static
   */
  public function setStore(SessionStore $store);

  /**
   * Sets Order items related to current booking.
   *
   * @param \Drupal\store\Entity\OrderItem[]
   */
  public function setOrderItems(array $order_items);

  /**
   * Sets Invoice related to current booking.
   *
   * @param \Drupal\store\Entity\Invoice
   */
  public function setInvoice(Invoice $invoice);

  /**
   * Sets Order related to current booking.
   *
   * @param \Drupal\store\Entity\StoreOrder
   */
  public function setOrder(StoreOrder $order);

  /**
   * Sets User related to current booking.
   *
   * @param \Drupal\user\Entity\User
   */
  public function setUser(User $user);

  /**
   * Gets Order related to current booking.
   *
   * @return \Drupal\store\Entity\StoreOrder
   */
  public function getOrder();

  /**
   * Gets User related to current booking.
   *
   * @return \Drupal\user\Entity\User
   */
  public function getUser();

  /**
   * Gets Invoice related to current booking.
   *
   * @return \Drupal\store\Entity\Invoice
   */
  public function getInvoice();

  /**
   * Gets Order items related to current booking.
   *
   * @return \Drupal\store\Entity\OrderItem[]
   */
  public function getOrderItems();

  /**
   * Creates User or return existing entity from DB related to current booking.
   *
   * @return \Drupal\store\Entity\StoreOrder
   */
  public function createUser();

  /**
   * Calculates Order total based on order items.
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   */
  public function calculateOrderTotal(StoreOrder $order);

  /**
   * Trigger SalesForce sync on success.
   * @param \Drupal\store\Entity\StoreOrder $order
   */
  public function bookingPaid(StoreOrder $order);

  /**
   * Trigger SalesForce sync on fail.
   * @param \Drupal\store\Entity\StoreOrder $order
   */
  public function bookingFailed(StoreOrder $order);

  /**
   * Trigger SalesForce sync on cancel.
   * @param \Drupal\store\Entity\StoreOrder $order
   */
  public function bookingCanceled(StoreOrder $order);

}
