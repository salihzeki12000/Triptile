<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\payment\Entity\TransactionInterface;
use Drupal\store\Price;

interface RefundAdapterInterface {

  /**
   * Checks if current adapter supports partial refunds.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return bool
   */
  public function supportsPartialRefund(TransactionInterface $transaction);

  /**
   * Checks if transaction can be refunded.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return bool
   */
  public function isTransactionRefundable(TransactionInterface $transaction);

  /**
   * Processes refund through the payment gateway.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $originalTransaction
   * @param \Drupal\payment\Entity\TransactionInterface $refundTransaction
   * @return bool
   */
  public function processRefund(TransactionInterface $originalTransaction, TransactionInterface $refundTransaction);

}
