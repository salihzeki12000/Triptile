<?php

namespace Drupal\store\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface for defining Customer profile entities.
 *
 * @ingroup store
 */
interface CustomerProfileInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Customer profile creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Customer profile.
   */
  public function getCreatedTime();

  /**
   * Sets the Customer profile creation timestamp.
   *
   * @param int $timestamp
   *   The Customer profile creation timestamp.
   *
   * @return \Drupal\store\Entity\CustomerProfileInterface
   *   The called Customer profile entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the billing profile owner.
   *
   * @return \Drupal\user\UserInterface
   */
  public function getOwner();

  /**
   * Sets the billing profile owner.
   *
   * @param \Drupal\user\UserInterface $user
   * @return static
   */
  public function setOwner(UserInterface $user);

  /**
   * Gets the related invoice.
   *
   * @return \Drupal\store\Entity\InvoiceInterface
   */
  public function getInvoice();

  /**
   * Sets the related invoice.
   *
   * @param \Drupal\store\Entity\InvoiceInterface $invoice
   * @return static
   */
  public function setInvoice(InvoiceInterface $invoice);

  /**
   * Gets customer billing address.
   *
   * @return \Drupal\address\Plugin\Field\FieldType\AddressItem
   */
  public function getAddress();

  /**
   * Sets customer billing address.
   *
   * @param array $address
   * @return static
   */
  public function setAddress(array $address);

  /**
   * Gets customer phone number.
   *
   * @return string
   */
  public function getPhoneNumber();

  /**
   * Sets customer phone number.
   *
   * @param string $phone_number
   * @return static
   */
  public function setPhoneNumber($phone_number);

  /**
   * Gets the customer email address.
   *
   * @return string
   */
  public function getEmail();

  /**
   * Sets the customer email address.
   *
   * @param $email
   * @return static
   */
  public function setEmail($email);

}
