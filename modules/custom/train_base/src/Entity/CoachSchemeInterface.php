<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Coach scheme entities.
 *
 * @ingroup train_base
 */
interface CoachSchemeInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Coach scheme name.
   *
   * @return string
   *   Name of the Coach scheme.
   */
  public function getName();

  /**
   * Sets the Coach scheme name.
   *
   * @param string $name
   *   The Coach scheme name.
   *
   * @return \Drupal\train_base\Entity\CoachSchemeInterface
   *   The called Coach scheme entity.
   */
  public function setName($name);

  /**
   * Gets the Coach scheme creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Coach scheme.
   */
  public function getCreatedTime();

  /**
   * Sets the Coach scheme creation timestamp.
   *
   * @param int $timestamp
   *   The Coach scheme creation timestamp.
   *
   * @return \Drupal\train_base\Entity\CoachSchemeInterface
   *   The called Coach scheme entity.
   */
  public function setCreatedTime($timestamp);

}
