<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\master\Entity\EntityEnabledInterface;

/**
 * Provides an interface for defining Seat preference entities.
 *
 * @ingroup train_base
 */
interface SeatPreferenceInterface extends ContentEntityInterface, EntityChangedInterface, ReferencingToSupplier, EntityEnabledInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Seat preference name.
   *
   * @return string
   *   Name of the Seat preference.
   */
  public function getName();

  /**
   * Sets the Seat preference name.
   *
   * @param string $name
   *   The Seat preference name.
   *
   * @return \Drupal\train_base\Entity\SeatPreferenceInterface
   *   The called Seat preference entity.
   */
  public function setName($name);

  /**
   * Gets the Seat preference creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Seat preference.
   */
  public function getCreatedTime();

  /**
   * Sets the Seat preference creation timestamp.
   *
   * @param int $timestamp
   *   The Seat preference creation timestamp.
   *
   * @return \Drupal\train_base\Entity\SeatPreferenceInterface
   *   The called Seat preference entity.
   */
  public function setCreatedTime($timestamp);

}
