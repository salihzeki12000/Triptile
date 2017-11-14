<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\payment\API\EcommpayAPI;
use Drupal\payment\Entity\Transaction;
use Drupal\payment\Entity\TransactionInterface;

/**
 * Class EcommpayCardPaymentAdapter
 *
 * @PaymentAdapter(
 *   id = "ecommpay_non3ds_card",
 *   label = @Translation("Ecommpay card (non-3ds)"),
 *   payment_system = "ecommpay"
 * )
 */
class EcommpayNon3dsCardPaymentAdapter extends EcommpayBaseAdapter implements OnSitePaymentAdapterInterface {

  /**
   * {@inheritdoc}
   */
  public function doPayment(TransactionInterface $transaction, array $payment_data, array $billing_data) {
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
    }

    if (isset($result)) {
      // TODO here we have to store real_amount value from the payment system.
      if ($result['code'] == EcommpayAPI::RESPONSE_CODE_SUCCESS) {
        $transaction->setRemoteId($result['transaction_id'])
          ->setStatus(Transaction::STATUS_SUCCESS)
          ->appendMessage('Payment processed successfully.');
      }
      elseif ($result['code'] == EcommpayAPI::RESPONSE_CODE_PENDING && !isset($result['pa_req'])) {
        $transaction->setRemoteId($result['transaction_id'])
          ->setStatus(Transaction::STATUS_PENDING)
          ->appendMessage('Transaction placed in the payment system.');
      }
      elseif ($result['code'] == EcommpayAPI::RESPONSE_CODE_PENDING && isset($result['pa_req'])) {
        $transaction->setRemoteId($result['transaction_id'])
          ->setStatus(Transaction::STATUS_FAILED)
          ->appendMessage('Payment system requires to process the transaction with 3DS.');
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

}
