<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Route message entities.
 *
 * @ingroup train_base
 */
interface RouteMessageInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Route message name.
   *
   * @return string
   *   Name of the Route message.
   */
  public function getName();

  /**
   * Sets the Route message name.
   *
   * @param string $name
   *   The Route message name.
   *
   * @return \Drupal\train_base\Entity\RouteMessageInterface
   *   The called Route message entity.
   */
  public function setName($name);

  /**
   * Gets the Route message creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Route message.
   */
  public function getCreatedTime();

  /**
   * Sets the Route message creation timestamp.
   *
   * @param int $timestamp
   *   The Route message creation timestamp.
   *
   * @return \Drupal\train_base\Entity\RouteMessageInterface
   *   The called Route message entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets supplier status.
   *
   * @return bool
   */
  public function isEnabled();
}
