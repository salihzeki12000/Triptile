<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\master\Entity\EntityEnabledInterface;

/**
 * Provides an interface for defining Coach class entities.
 *
 * @ingroup train_base
 */
interface CoachClassInterface extends ContentEntityInterface, EntityChangedInterface, ReferencingToSupplier, EntityEnabledInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Coach class name.
   *
   * @return string
   *   Name of the Coach class.
   */
  public function getName();

  /**
   * Sets the Coach class name.
   *
   * @param string $name
   *   The Coach class name.
   *
   * @return \Drupal\train_base\Entity\CoachClassInterface
   *   The called Coach class entity.
   */
  public function setName($name);

  /**
   * Gets the Coach class code.
   *
   * @return string
   */
  public function getCode();

  /**
   * Set the Coach class code.
   *
   * @param string $code
   * @return static
   */
  public function setCode($code);

  /**
   * Gets the Coach class description.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Gets the Coach class car services.
   *
   * @return \Drupal\train_base\Entity\CarService[]
   */
  public function getCarServices();

  /**
   * Gets the Coach class creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Coach class.
   */
  public function getCreatedTime();

  /**
   * Sets the Coach class creation timestamp.
   *
   * @param int $timestamp
   *   The Coach class creation timestamp.
   *
   * @return \Drupal\train_base\Entity\CoachClassInterface
   *   The called Coach class entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets attached car class gallery
   *
   * @return \Drupal\node\Entity\Node
   */
  public function getGallery();

  /**
   * Set supplier entity.
   *
   * @param Supplier $supplier
   * @return static
   */
  public function setSupplier($supplier);

  /**
   * Gets train brand entities.
   *
   * @return \Drupal\train_base\Entity\TrainBrand[]
   */
  public function getTrainBrands();

}
