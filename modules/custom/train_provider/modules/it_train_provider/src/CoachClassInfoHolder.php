<?php

namespace Drupal\it_train_provider;

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
   * Unique identifier of the journey.
   *
   * @var string
   */
  protected $journeySellKey;

  /**
   * Identifier of the fare.
   *
   * @var string
   */
  protected $fareSellKey;

  /**
   * Get the journeySellKey.
   *
   * @return string
   */
  public function getJourneySellKey() {
    return $this->journeySellKey;
  }

  /**
   * Set the journeySellKey.
   *
   * @param string $journeySellKey
   * @return static
   */
  public function setJourneySellKey($journeySellKey) {
    $this->journeySellKey = $journeySellKey;
    return $this;
  }

  /**
   * Get the fareSellKey.
   *
   * @return string
   */
  public function getFareSellKey() {
    return $this->fareSellKey;
  }

  /**
   * Set the fareSellKey.
   *
   * @param string $fareSellKey
   * @return static
   */
  public function setFareSellKey($fareSellKey) {
    $this->fareSellKey = $fareSellKey;
    return $this;
  }

}