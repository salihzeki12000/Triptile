<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\Core\Url;
use Drupal\payment\Entity\TransactionInterface;

interface OffSitePaymentAdapterInterface extends PaymentAdapterInterface {

  /**
   * Lets adapter to initialize payment.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @param array $payment_data
   * @param array $billing_data
   * @return bool
   */
  public function initPayment(TransactionInterface $transaction, array $payment_data, array $billing_data);

  /**
   * Allows adapter to complete payment.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return static
   */
  public function completePayment(TransactionInterface $transaction);

  /**
   * Gets the payment URL where user will be redirected to finish the payment.
   *
   * @return \Drupal\Core\Url
   */
  public function getPaymentUrl();

  /**
   * Sets the URL where user will be redirected after he successfully finishes the payment.
   *
   * @param \Drupal\Core\Url $url
   * @return static
   */
  public function setSuccessUrl(Url $url);

  /**
   * Sets the URL where user will be redirected after he cancels the payment.
   *
   * @param \Drupal\Core\Url $url
   * @return static
   */
  public function setCancelUrl(Url $url);

  /**
   * Sets the URL where user will be redirected if payment fails.
   *
   * @param \Drupal\Core\Url $url
   * @return static
   */
  public function setFailUrl(Url $url);

}
