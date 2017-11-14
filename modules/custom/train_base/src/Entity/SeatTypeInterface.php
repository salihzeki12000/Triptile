<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\master\Entity\EntityEnabledInterface;

/**
 * Provides an interface for defining Seat type entities.
 *
 * @ingroup train_base
 */
interface SeatTypeInterface extends ContentEntityInterface, EntityChangedInterface, ReferencingToSupplier, EntityEnabledInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Seat type name.
   *
   * @return string
   *   Name of the Seat type.
   */
  public function getName();

  /**
   * Sets the Seat type name.
   *
   * @param string $name
   *   The Seat type name.
   *
   * @return \Drupal\train_base\Entity\SeatTypeInterface
   *   The called Seat type entity.
   */
  public function setName($name);

  /**
   * Gets the Seat type creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Seat type.
   */
  public function getCreatedTime();

  /**
   * Sets the Seat type creation timestamp.
   *
   * @param int $timestamp
   *   The Seat type creation timestamp.
   *
   * @return \Drupal\train_base\Entity\SeatTypeInterface
   *   The called Seat type entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets capacity.
   *
   * @return int
   */
  public function getCapacity();

}
