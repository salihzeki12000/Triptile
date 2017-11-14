<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\API\PaypalAPI;
use Drupal\payment\Entity\Transaction;
use Drupal\payment\Entity\TransactionInterface;
use Drupal\store\Price;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PaypalBase
 */
abstract class PaypalBaseAdapter extends PaymentBaseAdapter implements RefundAdapterInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['username'] = '';
    $config['password'] = '';
    $config['signature'] = '';

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API username'),
      '#default_value' => $this->configuration['username'],
      '#required' => true,
    ];

    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API password'),
      '#default_value' => $this->configuration['password'],
      '#required' => true,
    ];

    $form['signature'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Signature'),
      '#default_value' => $this->configuration['signature'],
      '#required' => true,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['username'] = $values['username'];
      $this->configuration['password'] = $values['password'];
      $this->configuration['signature'] = $values['signature'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncTransactionStatus(TransactionInterface $transaction) {
    $result = $this->getAPI()
      ->setTransaction($transaction)
      ->getRemoteTransaction();
    $this->doTransactionSync($transaction, $result);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function processTransactionUpdateRequest(TransactionInterface $transaction, Request $request) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isTransactionRefundable(TransactionInterface $transaction) {
    return (time() - $transaction->getCreatedTime()) < (3600 * 24 * 180);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsPartialRefund(TransactionInterface $transaction) {
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function processRefund(TransactionInterface $originalTransaction, TransactionInterface $refundTransaction) {
    try {
      $result = $this->getAPI()
        ->setTransaction($refundTransaction)
        ->setOriginalTransaction($originalTransaction)
        ->refundTransaction();
    }
    catch (\Exception $exception) {
      $refundTransaction->setStatus(Transaction::STATUS_FAILED)
        ->appendMessage('Refund failed.');
    }

    if (isset($result)) {
      $refundTransaction->setRemoteStatus($result['REFUNDSTATUS']);
      switch ($result['REFUNDSTATUS']) {
        case PaypalAPI::REFUNDSTATUS_NONE:
          $refundTransaction->setStatus(Transaction::STATUS_FAILED);
          break;
        case PaypalAPI::REFUNDSTATUS_DELAYED:
          $refundTransaction->setStatus(Transaction::STATUS_PENDING);
          break;
        case PaypalAPI::REFUNDSTATUS_INSTANT:
          $refundTransaction->setStatus(Transaction::STATUS_SUCCESS);
          break;
      }
      if (in_array($result['ACK'], [PaypalAPI::ACK_SUCCESS, PaypalAPI::ACK_SUCCESS_WITH_WARNING])) {
        $refundTransaction->setRemoteId($result['REFUNDTRANSACTIONID']);
        $refundTransaction->appendMessage('Refund processed successfully.');
      }
      elseif (in_array($result['ACK'], [PaypalAPI::ACK_FAILURE, PaypalAPI::ACK_FAILURE_WITH_WARNING])) {
        $refundTransaction->appendMessage('Refund failed.')
          ->appendMessage('Error code: ' . $result['L_ERRORCODE0'] . "\n" . $result['L_SHORTMESSAGE0'] . "\n" . $result['L_LONGMESSAGE0']);
        if (isset($result['REFUNDTRANSACTIONID'])) {
          $refundTransaction->setRemoteId($result['REFUNDTRANSACTIONID']);
        }
      }
    }

    return in_array($refundTransaction->getStatus(), [Transaction::STATUS_SUCCESS, Transaction::STATUS_PENDING]);
  }

  /**
   * Maps status of a paypal transaction to our internal status.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @param array $result
   */
  protected function doTransactionSync(TransactionInterface $transaction, array $result) {
    if (isset($result['PAYMENTSTATUS'])) {
      $transaction->setRemoteStatus($result['PAYMENTSTATUS']);

      switch ($result['PAYMENTSTATUS']) {
        case PaypalAPI::PAYMENTSTATUS_NONE:
        case PaypalAPI::PAYMENTSTATUS_DENIED:
        case PaypalAPI::PAYMENTSTATUS_EXPIRED:
        case PaypalAPI::PAYMENTSTATUS_FAILED:
        case PaypalAPI::PAYMENTSTATUS_REVERSED:
        case PaypalAPI::PAYMENTSTATUS_VOIDED:
          $transaction->setStatus(Transaction::STATUS_FAILED);
          break;

        case PaypalAPI::PAYMENTSTATUS_IN_PROGRESS:
        case PaypalAPI::PAYMENTSTATUS_PENDING:
        case PaypalAPI::PAYMENTSTATUS_COMPLETED_FUNDS_HELD:
          $transaction->setStatus(Transaction::STATUS_PENDING);
          break;

        case PaypalAPI::PAYMENTSTATUS_COMPLETED:
        case PaypalAPI::PAYMENTSTATUS_PROCESSED:
          $transaction->setStatus(Transaction::STATUS_SUCCESS);
          break;

        case PaypalAPI::PAYMENTSTATUS_PARTIALLY_REFUNDED:
          $transaction->setStatus(Transaction::STATUS_PARTIALLY_REFUNDED);
          break;

        case PaypalAPI::PAYMENTSTATUS_REFUNDED:
          $transaction->setStatus(Transaction::STATUS_REFUNDED);
          break;
      }
    }
    else {
      $transaction->setStatus(Transaction::STATUS_FAILED);
    }
  }

}
