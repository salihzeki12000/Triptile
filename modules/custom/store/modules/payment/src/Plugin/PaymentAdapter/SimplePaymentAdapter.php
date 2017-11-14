<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Entity\Transaction;
use Drupal\payment\Entity\TransactionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Simple
 *
 * @PaymentAdapter(
 *   id = "simple",
 *   label = @Translation("Simple"),
 *   payment_system = "simple"
 * )
 */
class SimplePaymentAdapter extends PaymentBaseAdapter implements OnSitePaymentAdapterInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['sandbox_mode'] = true;
    $config['supported_currencies'] = ['USD'];
    $config['payment_type'] = 'success';
    return $config;
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['payment_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment type'),
      '#options' => [
        'success' => $this->t('Success'),
        'pending' => $this->t('Pending'),
        'pending_success' => $this->t('Pending and Success'),
        'pending_failed' => $this->t('Pending and Failed'),
        'failed' => $this->t('Failed'),
      ],
      '#default_value' => $this->configuration['payment_type'],
    ];

    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['payment_type'] = $values['payment_type'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function doPayment(TransactionInterface $transaction, array $payment_data, array $billing_data) {
    switch ($this->configuration['payment_type']) {
      case 'success':
        $this->doSuccessPayment($transaction);
        break;
      case 'pending_success':
      case 'pending_failed':
      case 'pending':
        $this->doPendingPayment($transaction);
        break;
      case 'failed':
        $this->doFailedPayment($transaction);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncTransactionStatus(TransactionInterface $transaction) {
    switch ($this->configuration['payment_type']) {
      case 'pending_success':
        $transaction->setStatus(Transaction::STATUS_SUCCESS);
        break;
      case 'pending_failed':
        $transaction->setStatus(Transaction::STATUS_FAILED);
    }
    $transaction->save();
  }

  /**
   * {@inheritdoc}
   */
  public function processTransactionUpdateRequest(TransactionInterface $transaction, Request $request) {
    return $this;
  }

  /**
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   */
  protected function doSuccessPayment(TransactionInterface $transaction) {
    $transaction->setStatus(Transaction::STATUS_SUCCESS)
      ->setRemoteStatus(Transaction::STATUS_SUCCESS)
      ->setAmount($this->calculateAmount($transaction))
      ->appendMessage('Success payment simulated successfully.')
      ->appendLog(['test' => 'Test log entry']);
  }

  /**
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   */
  protected function doPendingPayment(TransactionInterface $transaction) {
    $transaction->setStatus(Transaction::STATUS_PENDING)
      ->setRemoteStatus(Transaction::STATUS_PENDING)
      ->setAmount($this->calculateAmount($transaction))
      ->appendMessage('Pending payment simulated successfully.')
      ->appendLog(['test' => 'Test log entry']);
  }

  /**
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   */
  protected function doFailedPayment(TransactionInterface $transaction) {
    $transaction->setStatus(Transaction::STATUS_FAILED)
      ->setRemoteStatus(Transaction::STATUS_FAILED)
      ->setAmount($this->calculateAmount($transaction))
      ->appendMessage('Failed payment simulated successfully.')
      ->appendLog(['test' => 'Test log entry']);
  }

}
