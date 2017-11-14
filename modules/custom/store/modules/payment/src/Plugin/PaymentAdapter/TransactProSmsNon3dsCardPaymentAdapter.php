<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\payment\Entity\Transaction;
use Drupal\payment\Entity\TransactionInterface;

/**
 * Class TransactProSmsCardPaymentAdapter
 *
 * @PaymentAdapter(
 *   id = "transact_pro_sms_non_3ds_card",
 *   label = @Translation("TransactPro SMS card (non-3ds)"),
 *   payment_system = "transact_pro"
 * )
 */
class TransactProSmsNon3dsCardPaymentAdapter extends TransactProBaseAdapter implements OnSitePaymentAdapterInterface {

  /**
   * {@inheritdoc}
   */
  public function doPayment(TransactionInterface $transaction, array $payment_data, array $billing_data) {
    $transaction->setAmount($this->calculateAmount($transaction));
    $api = $this->getAPI();
    $initResponse = $api->setTransaction($transaction)
      ->setCreditCardData($payment_data)
      ->setBillingProfile($transaction->getInvoice()->getCustomerProfile())
      ->init();

    if (isset($initResponse['OK']) && isset($initResponse['RedirectOnSite'])) {
      $this->logger->error('Attempt to process payment through 3ds terminal using non-3ds adapter.');
      $transaction->setRemoteId($initResponse['OK'])
        ->setStatus(Transaction::STATUS_FAILED)
        ->appendMessage('Attempted to process payment through 3ds terminal using non-3ds adapter.');
    }
    elseif (isset($initResponse['ERROR'])) {
      $transaction->setStatus(Transaction::STATUS_FAILED)
        ->appendMessage('Error occurred during transaction initialization. ERROR: ' . $initResponse['ERROR']);
    }
    elseif (isset($initResponse['OK'])) {
      $transaction->setRemoteId($initResponse['OK'])
        ->setStatus(Transaction::STATUS_PENDING)
        ->appendMessage('Transaction initialized successfully');

      $chargeResponse = $api->charge();
      if (isset($chargeResponse['Status'])) {
        switch ($chargeResponse['Status']) {
          case 'Failure':
            $transaction->setStatus(Transaction::STATUS_FAILED)
              ->appendMessage('Error occurred during charge.');
            break;

          case 'Pending':
            $transaction->setStatus(Transaction::STATUS_PENDING)
              ->appendMessage('Charge is delayed.');
            break;

          case 'Success':
            $transaction->setStatus(Transaction::STATUS_SUCCESS)
              ->appendMessage('Charge completed successfully.');
            break;
        }
        $transaction->setRemoteStatus($chargeResponse['Status']);
      }
      else {
        $transaction->setStatus(Transaction::STATUS_FAILED)
          ->appendMessage('Error occurred during charge.');
      }
    }
  }

}
