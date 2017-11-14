<?php

namespace Drupal\amadeus_train_provider;

use Drupal\train_provider\CoachClassInfoHolder as CoachClassInfoHolderBase;


/**
 * Class CoachClassInfoHolder
 *
 * @package Drupal\train_provider
 *
 * @todo Rewrite it to use TypedData API
 */
class CoachClassInfoHolder extends CoachClassInfoHolderBase {

  protected $fareOfferId;

  /**
   * Gets the fareOfferId.
   *
   * @return int
   */
  public function getFareOfferId() {
    return $this->fareOfferId;
  }

  /**
   * Sets the fareOfferId.
   *
   * @param int $fareOfferId
   * @return static
   */
  public function setFareOfferId($fareOfferId) {
    $this->fareOfferId = $fareOfferId;
    return $this;
  }

}