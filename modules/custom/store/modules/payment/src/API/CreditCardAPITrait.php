<?php

namespace Drupal\payment\API;

trait CreditCardAPITrait {

  /**
   * @var array
   */
  protected $creditCardData;

  /**
   * Sets credit card data that will be charged.
   *
   * @param array $credit_card_data
   * @return static
   */
  public function setCreditCardData(array $credit_card_data) {
    $this->creditCardData = $credit_card_data;
    return $this;
  }

}
