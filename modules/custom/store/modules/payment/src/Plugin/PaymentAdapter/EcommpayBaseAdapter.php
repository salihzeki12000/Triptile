<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\API\EcommpayAPI;
use Drupal\payment\Entity\Transaction;
use Drupal\payment\Entity\TransactionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EcommpayBase
 * @package Drupal\payment\Plugin\PaymentAdapter
 */
abstract class EcommpayBaseAdapter extends PaymentBaseAdapter implements RefundAdapterInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['site_id'] = '';
    $config['salt'] = '';
    $config['proxy'] = '';

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site ID'),
      '#default_value' => $this->configuration['site_id'],
      '#required' => true,
    ];

    $form['salt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Salt'),
      '#default_value' => $this->configuration['salt'],
      '#required' => true,
    ];

    // TODO hide thise field from form if Sandbox is not checked.
    $form['proxy'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Proxy'),
      '#default_value' => $this->configuration['proxy'],
      '#description' => $this->t('This option will be used only if Sandbox is used.')
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

      $this->configuration['site_id'] = $values['site_id'];
      $this->configuration['salt'] = $values['salt'];
      // TODO clear the value if Sandbox is not used.
      $this->configuration['proxy'] = $values['proxy'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncTransactionStatus(TransactionInterface $transaction) {
    $orderInfo = $this->getAPI()
      ->setTransaction($transaction)
      ->getOrderInfo();

    foreach ($orderInfo['callbacks'] as $callback) {
      if ($callback['transaction_id'] == $transaction->getRemoteId()) {
        $this->processTransactionData($transaction, $callback);
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function processTransactionUpdateRequest(TransactionInterface $transaction, Request $request) {
    if ($request->query->get('signature')) {
      $params = $request->query->all();
      unset($params['signature']);
      if ($this->getAPI()->getSignature($params) == $request->query->get('signature')) {
        $transaction->appendLog($request->query->all());
        $this->processTransactionData($transaction, $request->query->all());
      }
      else {
        $this->logger->alert('Attempt to send a fake ecommpay notification.');
      }
    }
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
  public function isTransactionRefundable(TransactionInterface $transaction) {
    // TODO: Find real conditions to define if a transaction is refundable.
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
        ->doRefund();
    }
    catch (\Exception $exception) {
      $refundTransaction->setStatus(Transaction::STATUS_FAILED)
        ->appendMessage('Refund failed.');
    }

    if (isset($result)) {
      if ($result['code'] == EcommpayAPI::RESPONSE_CODE_SUCCESS) {
        $refundTransaction->setStatus(Transaction::STATUS_SUCCESS)
          ->appendMessage('Refund processed successfully.');
      }
      elseif ($result['code'] == EcommpayAPI::RESPONSE_CODE_PENDING) {
        $refundTransaction->setStatus(Transaction::STATUS_PENDING)
          ->appendMessage('Refund request sent.');
      }
      else {
        $refundTransaction->setStatus(Transaction::STATUS_FAILED)
          ->appendMessage('Refund failed.');
        if (isset($result['message'])) {
          $refundTransaction->appendMessage('Error code: ' . $result['code'] . "\n" . $result['message']);
        }
      }
      if (!empty($result['transaction_id'])) {
        $refundTransaction->setRemoteId($result['transaction_id']);
        $this->syncTransactionStatus($refundTransaction);
      }
    }

    return in_array($refundTransaction->getStatus(), [Transaction::STATUS_SUCCESS, Transaction::STATUS_PENDING]);
  }

  /**
   * Processes data from ecommpay and updates transaction.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @param array $transactionData
   */
  protected function processTransactionData(TransactionInterface $transaction, array $transactionData) {
    switch ($transactionData['status_id']) {
      case EcommpayAPI::TRANSACTION_STATUS_SUCCESS:
        $transaction->setStatus(Transaction::STATUS_SUCCESS);
        break;
      case EcommpayAPI::TRANSACTION_STATUS_EXTERNAL_PROCESSING:
      case EcommpayAPI::TRANSACTION_STATUS_AWAITING_CONFIRMATION:
        $transaction->setStatus(Transaction::STATUS_PENDING);
        break;
      case EcommpayAPI::TRANSACTION_STATUS_VOID:
      case EcommpayAPI::TRANSACTION_STATUS_PROCESSOR_DECLINE:
      case EcommpayAPI::TRANSACTION_STATUS_FRAUDSTOP_DECLINE:
      case EcommpayAPI::TRANSACTION_STATUS_MPI_DECLINE:
      case EcommpayAPI::TRANSACTION_STATUS_SYSTEM_FAILURE:
      case EcommpayAPI::TRANSACTION_STATUS_EXPIRED:
      case EcommpayAPI::TRANSACTION_STATUS_CANCELED:
      case EcommpayAPI::TRANSACTION_STATUS_INTERNAL_ERROR:
        $transaction->setStatus(Transaction::STATUS_FAILED);
        break;
    }
    $transaction->setRemoteStatus($transactionData['status_id']);
  }

}
