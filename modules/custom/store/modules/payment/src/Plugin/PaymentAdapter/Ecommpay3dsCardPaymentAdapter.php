<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\Core\Url;
use Drupal\payment\API\EcommpayAPI;
use Drupal\payment\Entity\Transaction;
use Drupal\payment\Entity\TransactionInterface;

/**
 * Class EcommpayCardPaymentAdapter
 *
 * @PaymentAdapter(
 *   id = "ecommpay_3ds_card",
 *   label = @Translation("Ecommpay card (3ds)"),
 *   payment_system = "ecommpay"
 * )
 */
class Ecommpay3dsCardPaymentAdapter extends EcommpayBaseAdapter implements OffSitePaymentAdapterInterface {

  use OffSitePaymentTrait;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Ecommpay3dsCardPaymentAdapter constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->tempStore = \Drupal::service('user.private_tempstore')->get('payment');
  }

  /**
   * {@inheritdoc}
   */
  public function initPayment(TransactionInterface $transaction, array $payment_data, array $billing_data) {
    $return = false;
    $transaction->setAmount($this->calculateAmount($transaction));
    try {
      $result = $this->getAPI()
        ->setConfig($this->configuration)
        ->setTransaction($transaction)
        ->setCreditCardData($payment_data)
        ->doPurchase();
    }
    catch (\Exception $exception) {
      $transaction->setStatus(Transaction::STATUS_FAILED)
        ->appendMessage('Internal error has occurred during the transaction process.');
      $this->logger->error($exception->getMessage());
      $return = false;
    }

    if (isset($result)) {
      // TODO here we have to store real_amount value from the payment system.
      if ($result['code'] == EcommpayAPI::RESPONSE_CODE_SUCCESS) {
        $transaction->setRemoteId($result['transaction_id'])
          ->setStatus(Transaction::STATUS_SUCCESS)
          ->appendMessage('Payment processed successfully.');
        // Update remote status on the transaction but avoid from any exceptions
        // since payment can be successful at this moment.
        try {
          $this->syncTransactionStatus($transaction);
        }
        catch (\Exception $exception) {
          $this->logger->error($exception->getMessage());
        }
        $this->paymentUrl = $this->successUrl;
        $return = TRUE;
      }
      elseif ($result['code'] == EcommpayAPI::RESPONSE_CODE_PENDING && !isset($result['pa_req'])) {
        $transaction->setRemoteId($result['transaction_id'])
          ->setStatus(Transaction::STATUS_PENDING)
          ->appendMessage('Transaction placed in the payment system.');
        // Update remote status on the transaction but avoid from any exceptions
        // since payment can be successful at this moment.
        try {
          $this->syncTransactionStatus($transaction);
        } catch (\Exception $exception) {
          $this->logger->error($exception->getMessage());
        }
        $this->paymentUrl = $this->successUrl;
        $return = TRUE;
      }
      elseif ($result['code'] == EcommpayAPI::RESPONSE_CODE_PENDING && isset($result['pa_req'])) {
        $transaction->setRemoteId($result['transaction_id'])
          ->setStatus(Transaction::STATUS_PENDING)
          ->appendMessage('Payment initiated successfully, 3D verification requested.');
        $this->tempStore->set('ecommpay.pa_req', $result['pa_req']);
        $this->tempStore->set('ecommpay.acs_url', $result['acs_url']);
        $this->tempStore->set('ecommpay.md', $result['md']);
        $this->tempStore->set('ecommpay.return_url', $this->successUrl->setAbsolute(TRUE)->toString());
        $this->paymentUrl = Url::fromRoute('payment.ecommpay_autoredirect');
        $return = TRUE;
      }
      else {
        if (isset($result['transaction_id'])) {
          $transaction->setRemoteId($result['transaction_id']);
        }
        $transaction->setStatus(Transaction::STATUS_FAILED)
          ->appendMessage('Payment failed.');
        if (isset($result['message'])) {
          $transaction->appendMessage('Error code: ' . $result['code'] . "\n" . $result['message']);
        }
        $return = FALSE;
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function completePayment(TransactionInterface $transaction) {
    $result = $this->getAPI()
      ->setTransaction($transaction)
      ->doComplete3ds();

    // TODO here we have to store real_amount value from the payment system.
    if ($result['code'] == EcommpayAPI::RESPONSE_CODE_SUCCESS) {
      $transaction->setStatus(Transaction::STATUS_SUCCESS)
        ->appendMessage('Payment completed successfully.');
    }
    elseif ($result['code'] == EcommpayAPI::RESPONSE_CODE_PENDING) {
      $transaction->setStatus(Transaction::STATUS_PENDING)
        ->appendMessage('User returned to site.');
    }
    else {
      $transaction->setStatus(Transaction::STATUS_FAILED)
        ->appendMessage('Payment completion failed.');
      if (isset($result['message'])) {
        $transaction->appendMessage('Error code: ' . $result['code'] . "\n" . $result['message']);
      }
    }

    // Update remote status on the transaction but avoid from any exceptions
    // since payment can be successful at this moment.
    try {
      $this->syncTransactionStatus($transaction);
    }
    catch (\Exception $exception) {
      $this->logger->error($exception->getMessage());
    }
  }

}
