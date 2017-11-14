<?php

namespace Drupal\payment\Plugin\PaymentMethod;

interface PayseraPaymentMethodInterface {

  /**
   * Gets the machine name of the payment method in Paysera.
   *
   * @return string
   */
  public function getPayseraPaymentName();

}
