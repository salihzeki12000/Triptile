<?php

namespace Drupal\master\Entity;

/**
 * Interface EntityEnabledInterface
 *
 * @package Drupal\master\Entity
 */
interface EntityEnabledInterface {

  /**
   * Returns true if entity status is active.
   *
   * @return bool
   */
  public function isEnabled();

}