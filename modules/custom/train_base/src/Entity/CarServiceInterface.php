<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\master\Entity\EntityEnabledInterface;

/**
 * Provides an interface for defining Car service entities.
 *
 * @ingroup train_base
 */
interface CarServiceInterface extends ContentEntityInterface, EntityChangedInterface, EntityEnabledInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Car service name.
   *
   * @return string
   *   Name of the Car service.
   */
  public function getName();

  /**
   * Sets the Car service name.
   *
   * @param string $name
   *   The Car service name.
   *
   * @return \Drupal\train_base\Entity\CarServiceInterface
   *   The called Car service entity.
   */
  public function setName($name);

  /**
   * Gets the Car service image.
   *
   * @return \Drupal\file\Entity\File
   */
  public function getImage();


  /**
   * Gets the Car service creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Car service.
   */
  public function getCreatedTime();

  /**
   * Sets the Car service creation timestamp.
   *
   * @param int $timestamp
   *   The Car service creation timestamp.
   *
   * @return \Drupal\train_base\Entity\CarServiceInterface
   *   The called Car service entity.
   */
  public function setCreatedTime($timestamp);

}
