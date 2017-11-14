<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\payment\API\PayseraAPI;
use Drupal\payment\Entity\Transaction;
use Drupal\payment\Entity\TransactionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PayseraPaymentAdapter
 *
 * @PaymentAdapter(
 *   id = "paysera",
 *   label = @Translation("Paysera"),
 *   payment_system = "paysera"
 * )
 */
class PayseraPaymentAdapter extends PaymentBaseAdapter implements OffSitePaymentAdapterInterface {

  use OffSitePaymentTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['projectid'] = '';
    $configuration['sign_password'] = '';

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['projectid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project id'),
      '#required' => true,
      '#default_value' => $this->configuration['projectid'],
    ];

    $form['sign_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sign password'),
      '#required' => true,
      '#default_value' => $this->configuration['sign_password'],
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

      $this->configuration['projectid'] = $values['projectid'];
      $this->configuration['sign_password'] = $values['sign_password'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function initPayment(TransactionInterface $transaction, array $payment_data, array $billing_data) {
    $transaction->setAmount($this->calculateAmount($transaction));
    if ($transaction->isNew()) {
      $transaction->save();
    }
    $this->paymentUrl = $this->getAPI()
      ->setConfig($this->configuration)
      ->setTransaction($transaction)
      ->setAcceptUrl($this->successUrl)
      ->setCancelUrl($this->cancelUrl)
      ->setCallbackUrl(Url::fromRoute('payment.paysera_callback', ['transaction' => $transaction->id()]))
      ->getPaymentUrl();

    $transaction->setStatus(Transaction::STATUS_PENDING);
    $transaction->appendMessage('Payment url generated successfully.');

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function completePayment(TransactionInterface $transaction) {
    $data = $this->getAPI()
      ->setConfig($this->configuration)
      ->checkResponse(\Drupal::request()->query->all());

    $transaction->appendLog($data);
    $transaction->appendMessage('User returned back to site.');

    $this->processTransactionData($transaction, $data);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function syncTransactionStatus(TransactionInterface $transaction) {
    // There is no way to request a transaction from Paysera.
  }

  /**
   * {@inheritdoc}
   */
  public function processTransactionUpdateRequest(TransactionInterface $transaction, Request $request) {
    $data = $this->getAPI()
      ->setConfig($this->configuration)
      ->checkResponse($request->query->all());

    $transaction->appendLog($data);
    $transaction->appendMessage('Received notification from Paysera.');

    $this->processTransactionData($transaction, $data);
  }

  protected function processTransactionData(TransactionInterface $transaction, array $data) {
    switch ($data['status']) {
      case PayseraAPI::PAYMENT_STATUS_FAILED:
        $transaction->setStatus(Transaction::STATUS_FAILED);
        break;
      case PayseraAPI::PAYMENT_STATUS_SUCCESS:
      case PayseraAPI::PAYMENT_STATUS_ADDITIONAL_INFORMATION:
        $transaction->setStatus(Transaction::STATUS_SUCCESS);
        break;
      case PayseraAPI::PAYMENT_STATUS_PENDING:
        $transaction->setStatus(Transaction::STATUS_PENDING);
        break;
    }

    $transaction->setRemoteStatus($data['status']);
  }

}
