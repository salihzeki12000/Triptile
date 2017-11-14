<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Entity\Transaction;
use Drupal\payment\Entity\TransactionInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class TransactProBaseAdapter extends PaymentBaseAdapter implements RefundAdapterInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();

    $config['guid'] = '';
    $config['password'] = '';
    $config['routing_string'] = '';
    $config['proxy'] = '';

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['guid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GUID'),
      '#required' => true,
      '#default_value' => $this->configuration['guid'],
    ];

    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Processing password'),
      '#required' => true,
      '#default_value' => $this->configuration['password'],
    ];

    $form['routing_string'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Routing string'),
      '#required' => true,
      '#default_value' => $this->configuration['routing_string'],
    ];

    // TODO hide this field from form if Sandbox is not checked.
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

      $this->configuration['guid'] = $values['guid'];
      $this->configuration['password'] = $values['password'];
      $this->configuration['routing_string'] = $values['routing_string'];
      $this->configuration['proxy'] = $values['proxy'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isTransactionRefundable(TransactionInterface $transaction) {
    $count = 0;
    foreach ($transaction->getChildTransactions() as $childTransaction) {
      if ($childTransaction->getType() == Transaction::TYPE_REFUND) {
        $count++;
      }
    }

    return $count < 7;
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
      $refundResponse = $this->getAPI()
        ->setTransaction($refundTransaction)
        ->setOriginalTransaction($originalTransaction)
        ->refund();
    }
    catch (\Exception $exception) {
      $refundTransaction->setStatus(Transaction::STATUS_FAILED)
        ->appendMessage('Refund failed.');
    }

    if (isset($refundResponse)) {
      if (array_key_exists('Refund Success', $refundResponse)) {
        $refundTransaction->setStatus(Transaction::STATUS_SUCCESS)
          ->setRemoteId($refundResponse['internal_refund_id'])
          ->appendMessage('Refund processed successfully.');
      }
      else {
        $refundTransaction->appendMessage('Refund failed.')
          ->setStatus(Transaction::STATUS_FAILED);

      }
    }

    return in_array($refundTransaction->getStatus(), [Transaction::STATUS_SUCCESS, Transaction::STATUS_PENDING]);
  }

  /**
   * {@inheritdoc}
   */
  public function syncTransactionStatus(TransactionInterface $transaction) {
    $statusRequestResponse = $this->getAPI()
      ->setTransaction($transaction)
      ->statusRequest();

    if (isset($statusRequestResponse['Status'])) {
      switch ($statusRequestResponse['Status']) {
        case 'failure':
          $transaction->setStatus(Transaction::STATUS_FAILED);
          break;

        case 'pending':
          $transaction->setStatus(Transaction::STATUS_PENDING);
          break;

        case 'success':
          $transaction->setStatus(Transaction::STATUS_SUCCESS);
          break;
      }
      $transaction->setRemoteStatus($statusRequestResponse['Status'])
        ->appendMessage('Transaction status updated.');
    }
    else {
      $transaction->appendMessage('Status request failed.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processTransactionUpdateRequest(TransactionInterface $transaction, Request $request) {
    // TODO: Implement processTransactionUpdateRequest() method.
  }

}
