<?php

namespace Drupal\salesforce\Entity;

use Drupal\Core\Entity\EntityStorageInterface;

trait MappableEntityTrait {

  protected $isOnPull = false;

  /**
   * Marks entity as pulled from salesforce.
   *
   * @return static
   */
  public function pullStart() {
    $this->isOnPull = true;
    return $this;
  }

  /**
   * Unmarks entity as pulled from salesforce.
   *
   * @return static
   */
  public function pullFinish() {
    $this->isOnPull = false;
    return $this;
  }

  /**
   * Checks if entity is pulled from salesforce.
   *
   * @return bool
   */
  public function isPullProcessing() {
    return $this->isOnPull;
  }

}
