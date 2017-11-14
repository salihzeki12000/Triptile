<?php


namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\payment\Entity\TransactionInterface;

interface RemoteBillingProfileAdapterInterface extends PaymentAdapterInterface {

  /**
   * Gets billing profile data from remote server.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return array
   */
  public function getBillingProfileData(TransactionInterface $transaction);

}
