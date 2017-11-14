<?php

namespace Drupal\train_base\Entity;

/**
 * Interface ReferencingToSupplier
 *
 * @package Drupal\train_base\Entity
 */
interface ReferencingToSupplier {

  /**
   * Gets referenced supplier entity.
   *
   * @return SupplierInterface|null
   */
  public function getSupplier();

}