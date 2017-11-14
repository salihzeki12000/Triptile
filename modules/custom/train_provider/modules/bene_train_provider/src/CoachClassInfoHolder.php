<?php

namespace Drupal\bene_train_provider;

use Drupal\train_provider\CoachClassInfoHolder as CoachClassInfoHolderBase;

/**
 * Class CoachClassInfoHolder
 *
 * @package Drupal\train_provider
 *
 * @todo Rewrite it to use TypedData API
 */
class CoachClassInfoHolder extends CoachClassInfoHolderBase {

  /**
   * Data about this Proposed Price.
   *
   * @var string
   */
  protected $proposedPrice;

  /**
   * Get the proposedPrice.
   *
   * @return string
   */
  public function getProposedPrice() {
    return $this->proposedPrice;
  }

  /**
   * Set the proposedPrice.
   *
   * @param array $proposedPrice
   * @return static
   */
  public function setProposedPrice($proposedPrice) {
    $this->proposedPrice = $proposedPrice;
    return $this;
  }

}