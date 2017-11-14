<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\API\PaypalAPI;
use Drupal\payment\Entity\Transaction;
use Drupal\payment\Entity\TransactionInterface;

/**
 * Class PaypalWPP
 *
 * @PaymentAdapter(
 *   id = "paypal_wpp",
 *   label = @Translation("Paypal WPP"),
 *   payment_system = "paypal"
 * )
 */
class PaypalWppAdapter extends PaypalBaseAdapter implements OnSitePaymentAdapterInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['soft_descriptor'] = '';
    $config['fake_billing_address'] = 0;

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['soft_descriptor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Soft descriptor'),
      '#description' => $this->t('Leave empty in order to use default descriptor configured in paypal account.'),
      '#default_value' => $this->configuration['soft_descriptor'],
    ];

    $form['fake_billing_address'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fake billing address'),
      '#default_value' => $this->configuration['fake_billing_address'],
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

      $this->configuration['fake_billing_address'] = $values['fake_billing_address'];
      $this->configuration['soft_descriptor'] = $values['soft_descriptor'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function doPayment(TransactionInterface $transaction, array $payment_data, array $billing_data) {
    $transaction->setAmount($this->calculateAmount($transaction));
    $result = $this->getAPI()
      ->setConfig($this->configuration)
      ->setTransaction($transaction)
      ->setCreditCardData($payment_data)
      ->setBillingProfile($transaction->getInvoice()->getCustomerProfile())
      ->doDirectPayment();

    if (in_array($result['ACK'], [PaypalAPI::ACK_SUCCESS, PaypalAPI::ACK_SUCCESS_WITH_WARNING])) {
      $transaction->setRemoteId($result['TRANSACTIONID'])
        ->setRemoteStatus($result['ACK'])
        ->setStatus(Transaction::STATUS_SUCCESS)
        ->appendMessage('Payment processed successfully.');
    }
    elseif (in_array($result['ACK'], [PaypalAPI::ACK_FAILURE, PaypalAPI::ACK_FAILURE_WITH_WARNING])) {
      $transaction->setRemoteStatus($result['ACK'])
        ->setStatus(Transaction::STATUS_FAILED)
        ->appendMessage('Payment failed.');
      if (isset($result['ACK'])) {
        $transaction->appendMessage('Error code: ' . $result['L_ERRORCODE0'] . "\n" . $result['L_SHORTMESSAGE0']
          . "\n" . $result['L_LONGMESSAGE0']);
      }
      if (isset($result['TRANSACTIONID'])) {
        $transaction->setRemoteId($result['TRANSACTIONID']);
      }
    }
  }

}
