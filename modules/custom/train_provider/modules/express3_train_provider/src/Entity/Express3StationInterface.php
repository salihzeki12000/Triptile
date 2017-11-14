<?php

namespace Drupal\express3_train_provider\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Express3station entities.
 *
 * @ingroup express3_train_provider
 */
interface Express3StationInterface extends  ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Express3station name.
   *
   * @return string
   *   Name of the Express3station.
   */
  public function getName();

  /**
   * Sets the Express3station name.
   *
   * @param string $name
   *   The Express3station name.
   *
   * @return \Drupal\express3_train_provider\Entity\Express3StationInterface
   *   The called Express3station entity.
   */
  public function setName($name);

  /**
   * Gets the Express3station code.
   *
   * @return string
   *   Code of the Express3station.
   */
  public function getCode();

  /**
   * Sets the Express3station code.
   *
   * @param string $code
   *   The Express3station code.
   *
   * @return \Drupal\express3_train_provider\Entity\Express3StationInterface
   *   The called Express3station entity.
   */
  public function setCode($code);

  /**
   * Gets the Express3station creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Express3station.
   */
  public function getCreatedTime();

  /**
   * Sets the Express3station creation timestamp.
   *
   * @param int $timestamp
   *   The Express3station creation timestamp.
   *
   * @return \Drupal\express3_train_provider\Entity\Express3StationInterface
   *   The called Express3station entity.
   */
  public function setCreatedTime($timestamp);
}
