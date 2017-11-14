<?php

namespace Drupal\salesforce\Entity;

interface MappableEntityInterface {

  /**
   * Marks entity as pulling from salesforce.
   *
   * @return static
   */
  public function pullStart();

  /**
   * Unmarks entity as pulling from salesforce.
   *
   * @return static
   */
  public function pullFinish();

  /**
   * Checks if entity is pulled from salesforce.
   *
   * @return bool
   */
  public function isPullProcessing();

}
