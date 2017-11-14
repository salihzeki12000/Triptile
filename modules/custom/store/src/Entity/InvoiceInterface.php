<?php

namespace Drupal\store\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\store\Price;
use Drupal\user\UserInterface;

/**
 * Provides an interface for defining Invoice entities.
 *
 * @ingroup store
 */
interface InvoiceInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Store order creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Store order.
   */
  public function getCreatedTime();

  /**
   * Sets the Store order creation timestamp.
   *
   * @param int $timestamp
   *   The Store order creation timestamp.
   *
   * @return \Drupal\store\Entity\StoreOrderInterface
   *   The called Store order entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the invoice amount.
   *
   * @return \Drupal\store\Price|null
   */
  public function getAmount();

  /**
   * Sets the invoice amount.
   *
   * @param \Drupal\store\Price $amount
   * @return static
   */
  public function setAmount(Price $amount);

  /**
   * Gets the invoice status.
   *
   * @return string
   */
  public function getStatus();

  /**
   * Sets the invoice status.
   *
   * @param string $status
   * @return static
   */
  public function setStatus($status);

  /**
   * Checks if the invoice can be paid.
   *
   * @return bool
   */
  public function isPayable();

  /**
   * Checks if the invoice is paid.
   *
   * @return bool
   */
  public function isPaid();


  /**
   * Checks if current invoice is visible to users.
   *
   * @return bool
   */
  public function isVisible();

  /**
   * Sets invoice visibility.
   *
   * @param bool $visibility
   * @return static
   */
  public function setVisibility($visibility);

  /**
   * Gets invoice description.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Sets invoice description.
   *
   * @param string $description
   * @return static
   */
  public function setDescription($description);

  /**
   * Gets invoice number.
   *
   * @return string
   */
  public function getInvoiceNumber();

  /**
   * Sets the invoice number.
   *
   * @param string $number
   * @return static
   */
  public function setInvoiceNumber($number);

  /**
   * Gets the customer profile
   *
   * @return \Drupal\store\Entity\CustomerProfile
   */
  public function getCustomerProfile();

  /**
   * Gets related order.
   *
   * @return \Drupal\store\Entity\StoreOrderInterface
   */
  public function getOrder();

  /**
   * Sets an order for the invoice.
   *
   * @param \Drupal\store\Entity\StoreOrderInterface $order
   * @return static
   */
  public function setOrder(StoreOrderInterface $order);

  /**
   * Gets the invoice owner.
   *
   * @return \Drupal\user\Entity\User
   */
  public function getUser();

  /**
   * Sets invoice user.
   *
   * @param \Drupal\user\UserInterface $user
   * @return static
   */
  public function setUser(UserInterface $user);

  /**
   * Gets all related transactions ordered by id.
   *
   * @return \Drupal\payment\Entity\Transaction[]
   */
  public function getTransactions();

  /**
   * Loads latest created transaction on the invoice.
   *
   * @return \Drupal\payment\Entity\Transaction|null
   */
  public function getLatestTransaction();

  /**
   * Gets the invoice expiration date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  public function getExpirationDate();

  /**
   * Sets the invoice expiration date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $expiration_date
   * @return static
   */
  public function setExpirationDate(DrupalDateTime $expiration_date);

}
