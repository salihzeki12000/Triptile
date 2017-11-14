<?php

namespace Drupal\it_train_provider;

use Drupal\train_provider\TrainInfoHolder as TrainInfoHolderBase;

class TrainInfoHolder extends TrainInfoHolderBase {

  /**
   * Session signature provided by BIG after the authentication.
   *
   * @var string
   */
  protected $signature;

  /**
   * Unique identifier of the journey.
   *
   * @var string
   */
  protected $journeySellKey;

  /**
   * Get the signature of the session.
   *
   * @return string
   */
  public function getSignature() {
    return $this->signature;
  }

  /**
   * Set the signature of the session.
   *
   * @param string $signature
   * @return $this
   */
  public function setSignature($signature) {
    $this->signature = $signature;
    return $this;
  }

  /**
   * Get the unique identifier of the journey.
   *
   * @return string
   */
  public function getJourneySellKey() {
    return $this->journeySellKey;
  }

  /**
   * Set the unique identifier of the journey.
   *
   * @param string $journeyKey
   * @return $this
   */
  public function setJourneySellKey($journeyKey) {
    $this->journeySellKey = $journeyKey;
    return $this;
  }

}