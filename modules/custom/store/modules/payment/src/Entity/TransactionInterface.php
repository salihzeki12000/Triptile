<?php

namespace Drupal\payment\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\store\Entity\InvoiceInterface;
use Drupal\store\Price;

/**
 * Provides an interface for defining Transaction entities.
 *
 * @ingroup store
 */
interface TransactionInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Transaction creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Transaction.
   */
  public function getCreatedTime();

  /**
   * Sets the Transaction creation timestamp.
   *
   * @param int $timestamp
   *   The Transaction creation timestamp.
   *
   * @return static
   *   The called Transaction entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the merchant used to process the transaction.
   *
   * @return \Drupal\payment\Entity\Merchant
   */
  public function getMerchant();

  /**
   * Sets the merchant used to process the transaction.
   *
   * @param \Drupal\payment\Entity\MerchantInterface $merchant
   * @return static
   */
  public function setMerchant(MerchantInterface $merchant);

  /**
   * Gets the payment method used to create transaction.
   *
   * @return string
   */
  public function getPaymentMethod();

  /**
   * Sets the payment method used to create transaction.
   *
   * @param string $payment_method
   * @return static
   */
  public function setPaymentMethod($payment_method);

  /**
   * Gets the transaction status
   *
   * @return string
   */
  public function getStatus();

  /**
   * Sets the transaction status.
   *
   * @param string $status
   * @return static
   */
  public function setStatus($status);

  /**
   * Gets the transaction status in the payment system used to process the transaction.
   *
   * @return string
   */
  public function getRemoteStatus();

  /**
   * Sets the transaction status in the payment system used to process the transaction.
   *
   * @param string $status
   * @return static
   */
  public function setRemoteStatus($status);

  /**
   * Gets the transaction type.
   *
   * @return string
   */
  public function getType();

  /**
   * Sets the transaction type.
   *
   * @param $type
   * @return static
   */
  public function setType($type);

  /**
   * Gets the parent transaction.
   *
   * @return static
   */
  public function getParentTransaction();

  /**
   * Sets the parent transaction.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return static
   */
  public function setParentTransaction(TransactionInterface $transaction);

  /**
   * Gets related invoice.
   *
   * @return \Drupal\store\Entity\InvoiceInterface
   */
  public function getInvoice();

  /**
   * Sets the related invoice.
   *
   * @param \Drupal\store\Entity\InvoiceInterface $invoice
   * @return static
   */
  public function setInvoice(InvoiceInterface $invoice);

  /**
   * Gets the transaction amount.
   *
   * @return \Drupal\store\Price|null
   */
  public function getAmount();

  /**
   * Sets the transaction amount.
   *
   * @param \Drupal\store\Price $amount
   * @return static
   */
  public function setAmount(Price $amount);

  /**
   * Gets the transaction original amount.
   *
   * @return \Drupal\store\Price|null
   */
  public function getOriginalAmount();

  /**
   * Sets the transaction original amount.
   *
   * @param \Drupal\store\Price $amount
   * @return static
   */
  public function setOriginalAmount(Price $amount);

  /**
   * Gets currency rate between transaction amount and original amount.
   *
   * @return float
   */
  public function getCurrencyRate();

  /**
   * Gets IP address of a client created the transaction.
   *
   * @return string
   */
  public function getIPAddress();

  /**
   * Gets the transaction owner.
   *
   * @return \Drupal\user\Entity\User
   */
  public function getOwner();

  /**
   * Gets transaction message.
   *
   * @return string
   */
  public function getMessage();

  /**
   * Appends a messages to the message field.
   *
   * @param string $message
   * @return static
   */
  public function appendMessage($message);

  /**
   * Gets transaction log.
   *
   * @return array
   */
  public function getLog();

  /**
   * Appends a record to transaction log.
   *
   * @param mixed $log
   * @return static
   */
  public function appendLog($log);

  /**
   * Gets the transaction id in the payment system.
   *
   * @return string
   */
  public function getRemoteId();

  /**
   * Sets the transaction id in the payment system.
   *
   * @param string $remote_id
   * @return static
   */
  public function setRemoteId($remote_id);

  /**
   * Checks if transaction is success.
   *
   * @return bool
   */
  public function isSuccess();

  /**
   * Checks if current transaction can be refunded.
   *
   * @return bool
   */
  public function isRefundable();

  /**
   * Gets the amount that can be refunded.
   *
   * @return \Drupal\store\Price
   */
  public function getRefundableAmount();

  /**
   * Gets all child transactions.
   *
   * @return \Drupal\payment\Entity\TransactionInterface[]
   */
  public function getChildTransactions();

}
