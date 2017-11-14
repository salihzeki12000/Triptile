<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\payment\Entity\Transaction;
use Drupal\payment\Plugin\PaymentMethodManager;
use Drupal\salesforce\SalesforceSync;
use Drupal\store\Entity\StoreOrder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentAPICallback extends ControllerBase {

  /**
   * @var \Drupal\payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * @var \Drupal\salesforce\SalesforceSync
   */
  protected $salesforceSync;

  /**
   * PaymentAPICallback constructor.
   * @param \Drupal\payment\Plugin\PaymentMethodManager $payment_method_manager
   */
  public function __construct(PaymentMethodManager $payment_method_manager, SalesforceSync $salesforce_sync) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->salesforceSync = $salesforce_sync;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.payment.payment_method'), $container->get('salesforce_sync'));
  }

  /**
   * Callback for Paypal IPN requests.
   *
   * @param \Drupal\payment\Entity\Transaction $transaction
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function paypalIPN(Transaction $transaction, Request $request) {
    $this->doProcessTransaction($transaction, $request);
    return new Response();
  }

  /**
   * Callback for ecommpay notification requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function ecommpayNotification(Request $request) {
    if ($transaction_id = $request->query->get('transaction_id')) {
      $transactions = $this->entityTypeManager()->getStorage('transaction')
        ->loadByProperties(['remote_id' => $transaction_id]);
      /** @var \Drupal\payment\Entity\Transaction $transaction */
      foreach ($transactions as $transaction) {
        $merchant = $transaction->getMerchant();
        if (in_array($merchant->getPaymentAdapter(), ['ecommpay_3ds_card', 'ecommpay_non3ds_card'])) {
          $this->doProcessTransaction($transaction, $request);
        }
      }
    }

    return new Response();
  }

  /**
   * Callback for notifications from Paysera.
   *
   * @param \Drupal\payment\Entity\Transaction $transaction
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function payseraCallback(Transaction $transaction, Request $request) {
    $this->doProcessTransaction($transaction, $request);

    return new Response('OK');
  }

  /**
   * Runs process of transaction update.
   *
   * @param \Drupal\payment\Entity\Transaction $transaction
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  protected function doProcessTransaction(Transaction $transaction, Request $request) {
    $config = $this->config('plugin.plugin_configuration.payment_method.' . $transaction->getPaymentMethod())->get();
    $invoice = $transaction->getInvoice();
    /** @var \Drupal\payment\Plugin\PaymentMethod\PaymentMethodBase $payment_method */
    $payment_method = $this->paymentMethodManager->createInstance($transaction->getPaymentMethod(), $config);
    $payment_method->setInvoice($invoice)
      ->processTransactionUpdateRequest($transaction, $request);

    // TODO Booking manager should be used to update order status.
    /** @var \Drupal\store\Entity\StoreOrder $order */
    if ($payment_method->invoiceIsPaid() && $order = $invoice->getOrder()) {
      if ($order->getStatus() == StoreOrder::STATUS_NEW || $order->getStatus() == StoreOrder::STATUS_FAILED) {
        $order->setStatus(StoreOrder::STATUS_PROCESSING)->save();
        $this->salesforceSync->entityCrud($order, SalesforceSync::OPERATION_UPDATE);
      }
    }
  }

}
