<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Train brand entities.
 *
 * @ingroup train_base
 */
interface TrainBrandInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Train brand name.
   *
   * @return string | null
   */
  public function getName();

  /**
   * Gets the Train brand creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Train.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Train brand creation timestamp.
   *
   * @param int $timestamp
   *   The Train creation timestamp.
   *
   * @return \Drupal\train_base\Entity\TrainBrandInterface
   *   The called Train entity.
   */
  public function setCreatedTime(int $timestamp);

}
