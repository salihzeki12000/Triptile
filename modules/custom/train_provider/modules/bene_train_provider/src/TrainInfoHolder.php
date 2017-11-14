<?php

namespace Drupal\bene_train_provider;

use Drupal\train_provider\TrainInfoHolder as TrainInfoHolderBase;

class TrainInfoHolder extends TrainInfoHolderBase {

  /**
   * Session signature provided by BIG after the authentication.
   *
   * @var string
   */
  protected $passengerListReply;

  /**
   * Get the passengerListReply.
   *
   * @return string
   */
  public function getPassengerListReply() {
    return $this->passengerListReply;
  }

  /**
   * Set the passengerListReply.
   *
   * @param string $passengerListReply
   * @return $this
   */
  public function setPassengerListReply($passengerListReply) {
    $this->passengerListReply = $passengerListReply;
    return $this;
  }

}