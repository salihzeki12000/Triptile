<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\master\Entity\EntityEnabledInterface;

/**
 * Provides an interface for defining Supplier entities.
 *
 * @ingroup train_base
 */
interface SupplierInterface extends ContentEntityInterface, EntityChangedInterface, EntityEnabledInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Supplier name.
   *
   * @return string
   *   Name of the Supplier.
   */
  public function getName();

  /**
   * Sets the Supplier name.
   *
   * @param string $name
   *   The Supplier name.
   *
   * @return \Drupal\train_base\Entity\SupplierInterface
   *   The called Supplier entity.
   */
  public function setName($name);

  /**
   * Gets supplier logo.
   *
   * @return \Drupal\file\Entity\File
   */
  public function getLogo();

  /**
   * Gets Minimal child age.
   *
   * @return integer
   */
  public function getMinChildAge();

  /**
   * Gets Maximal child age.
   *
   * @return integer
   */
  public function getMaxChildAge();

  /**
   * Check on the child's age.
   *
   * @param int $age
   *
   * @return bool
   */
  public function isChild($age);

  /**
   * Check on the infant's age.
   *
   * @param int $age
   *
   * @return bool
   */
  public function isInfant($age);

  /**
   * Gets passenger form type.
   *
   * @return string
   */
  public function getPassengerFormType();


  /**
   * Gets the Supplier creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Supplier.
   */
  public function getCreatedTime();

  /**
   * Sets the Supplier creation timestamp.
   *
   * @param int $timestamp
   *   The Supplier creation timestamp.
   *
   * @return \Drupal\train_base\Entity\SupplierInterface
   *   The called Supplier entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets supplier code.
   *
   * @return string
   */
  public function getCode();

  /**
   * Set supplier code.
   *
   * @param $supplierCode
   * @return static
   */
  public function setCode($supplierCode);

  /**
   * Gets supplier email.
   *
   * @return string
   */
  public function getEmail();

  /**
   * Gets create payable invoice parameter.
   *
   * @return bool
   */
  public function getCreatePayableInvoice();

  /**
   * Gets running balance ID.
   *
   * @return string
   */
  public function getRunningBalanceId();

  /**
   * Gets maximal order depth.
   *
   * @return int
   */
  public function getMaxOrderDepth();

  /**
   * Gets supplier currency.
   *
   * @return string
   */
  public function getCurrency();

}
