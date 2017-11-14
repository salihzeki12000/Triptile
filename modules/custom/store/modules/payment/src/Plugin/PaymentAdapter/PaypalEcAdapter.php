<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use Drupal\address\FieldHelper;
use Drupal\payment\API\PaypalAPI;
use Drupal\payment\Entity\Transaction;
use Drupal\payment\Entity\TransactionInterface;

/**
 * Class PaypalWPP
 *
 * @PaymentAdapter(
 *   id = "paypal_ec",
 *   label = @Translation("Paypal EC"),
 *   payment_system = "paypal"
 * )
 */
class PaypalEcAdapter extends PaypalBaseAdapter implements OffSitePaymentAdapterInterface, RemoteBillingProfileAdapterInterface  {

  use OffSitePaymentTrait;

  /**
   * {@inheritdoc}
   */
  public function initPayment(TransactionInterface $transaction, array $payment_data, array $billing_data) {
    $transaction->setAmount($this->calculateAmount($transaction));
    if ($transaction->isNew()) {
      $transaction->save();
    }
    $result = $this->getAPI()
      ->setConfig($this->configuration)
      ->setTransaction($transaction)
      ->setCancelUrl($this->cancelUrl)
      ->setReturnUrl($this->successUrl)
      ->setExpressCheckout();

    $url = null;
    if (in_array($result['ACK'], [PaypalAPI::ACK_SUCCESS, PaypalAPI::ACK_SUCCESS_WITH_WARNING])) {
      $transaction->setData('paypal_ec_token', $result['TOKEN']);
      $this->paymentUrl = $this->getAPI()->getCheckoutPageUrl($result['TOKEN']);
      $transaction->setStatus(Transaction::STATUS_PENDING)
        ->appendMessage('Payment initiated successfully.');
      return true;
    }
    else {
      $transaction->setStatus(Transaction::STATUS_FAILED)
        ->appendMessage('Payment initialization failed.');
      if (isset($result['ACK'])) {
        $transaction->appendMessage('Error code: ' . $result['L_ERRORCODE0'] . "\n" . $result['L_SHORTMESSAGE0']
          . "\n" . $result['L_LONGMESSAGE0']);
      }
      return false;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingProfileData(TransactionInterface $transaction) {
    $result = $this->getAPI()
      ->setTransaction($transaction)
      ->setToken($transaction->getData('paypal_ec_token'))
      ->getExpressCheckoutDetails();

    $billing_profile_data = [
      'country_code' => $result['COUNTRYCODE'],
      FieldHelper::getPropertyName(AddressField::GIVEN_NAME) => $result['FIRSTNAME'],
      FieldHelper::getPropertyName(AddressField::FAMILY_NAME) => $result['LASTNAME'],

      'phone_number' => isset($result['PHONENUM']) ? $result['PHONENUM'] : '',
      'email' => $result['EMAIL'],
    ];

    if (isset($result['MIDDLENAME'])) {
      $result[FieldHelper::getPropertyName(AddressField::ADDITIONAL_NAME)] = $result['MIDDLENAME'];
    }

    return $billing_profile_data;
  }

  /**
   * {@inheritdoc}
   */
  public function completePayment(TransactionInterface $transaction) {
    $api = $this->getAPI();
    $result = $api->setTransaction($transaction)
      ->setToken($transaction->getData('paypal_ec_token'))
      ->getExpressCheckoutDetails();

    if (in_array($result['ACK'], [PaypalAPI::ACK_SUCCESS, PaypalAPI::ACK_SUCCESS_WITH_WARNING])) {
      $payer_id = $result['PAYERID'];
      $result = $api->setPayerId($payer_id)
        ->doExpressCheckoutPayment();

      $transaction->setRemoteId($result['PAYMENTINFO_0_TRANSACTIONID']);
      try {
        $result = $api->getRemoteTransaction();
        $this->doTransactionSync($transaction, $result);
      }
      catch (\Exception $e) {
        // prevent displaying error if we simply can't get transaction from paypal.
        watchdog_exception('payment', $e);
      }

      if (in_array($transaction->getStatus(), [Transaction::STATUS_PENDING, Transaction::STATUS_SUCCESS])) {
        $transaction->appendMessage('Payment completed successfully.');
      }
      else {
        $transaction->appendMessage('Payment completion failed.');
        $transaction->appendMessage('Error code: ' . $result['L_ERRORCODE0'] . "\n" . $result['L_SHORTMESSAGE0']
          . "\n" . $result['L_LONGMESSAGE0']);

      }
    }
    else {
      $transaction->setStatus(Transaction::STATUS_FAILED)
        ->appendMessage('Payment completion failed: can\'t load payer id.' );
      if (isset($result['ACK'])) {
        $transaction->appendMessage('Error code: ' . $result['L_ERRORCODE0'] . "\n" . $result['L_SHORTMESSAGE0']
          . "\n" . $result['L_LONGMESSAGE0']);
      }
    }
  }

}
