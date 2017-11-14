<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\payment\Entity\TransactionInterface;

interface OnSitePaymentAdapterInterface extends PaymentAdapterInterface {

  /**
   * Process a payment through remote gateway.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @param array $payment_data
   * @param array $billing_data
   * @return static
   */
  public function doPayment(TransactionInterface $transaction, array $payment_data, array $billing_data);

}
