<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\master\Entity\EntityEnabledInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Train class entities.
 *
 * @ingroup train_base
 */
interface TrainClassInterface extends ContentEntityInterface, EntityChangedInterface, ReferencingToSupplier, EntityEnabledInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Train class name.
   *
   * @return string
   *   Name of the Train class.
   */
  public function getName();

  /**
   * Gets train class description
   *
   * @return string
   */
  public function getDescription();

  /**
   * Sets the Train class name.
   *
   * @param string $name
   *   The Train class name.
   *
   * @return \Drupal\train_base\Entity\TrainClassInterface
   *   The called Train class entity.
   */
  public function setName($name);

  /**
   * Gets the Train class creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Train class.
   */
  public function getCreatedTime();

  /**
   * Sets the Train class creation timestamp.
   *
   * @param int $timestamp
   *   The Train class creation timestamp.
   *
   * @return \Drupal\train_base\Entity\TrainClassInterface
   *   The called Train class entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Sets the Train class code.
   *
   * @param $trainClassSupplier
   * @return static
   */
  public function setCode($trainClassSupplier);

  /**
   * Sets the Train class code.
   *
   * @param Supplier $supplier
   * @return static
   */
  public function setSupplier($supplier);

}
