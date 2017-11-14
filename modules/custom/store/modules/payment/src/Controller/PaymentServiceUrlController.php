<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\payment\Plugin\PaymentMethodManager;
use Drupal\salesforce\SalesforceSync;
use Drupal\store\Entity\Invoice;
use Drupal\store\Entity\StoreOrder;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class PaymentServiceUrlController
 *
 * Serves cancel and return payment urls.
 *
 * @package Drupal\payment\Controller
 */
class PaymentServiceUrlController extends ControllerBase {

  /**
   * @var \Drupal\payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\salesforce\SalesforceSync
   */
  protected $salesforceSync;

  /**
   * PaymentServiceUrlController constructor.
   *
   * @param \Drupal\payment\Plugin\PaymentMethodManager $payment_method_manager
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\salesforce\SalesforceSync $salesforce_sync
   */
  public function __construct(PaymentMethodManager $payment_method_manager, EntityTypeManager $entity_type_manager, SalesforceSync $salesforce_sync) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->salesforceSync = $salesforce_sync;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.payment.payment_method'),
      $container->get('entity_type.manager'),
      $container->get('salesforce_sync')
    );
  }

  public function success($invoice, $payment_method) {
    /** @var \Drupal\store\Entity\Invoice $invoice */
    $invoice = $this->entityTypeManager->getStorage('invoice')->load($invoice);
    $config = $this->config('plugin.plugin_configuration.payment_method.' . $payment_method)->get();
    /** @var \Drupal\payment\Plugin\PaymentMethod\PaymentMethodBase $plugin */
    $plugin = $this->paymentMethodManager->createInstance($payment_method, $config);
    $plugin->setInvoice($invoice)
      ->paymentReturned();

    if ($plugin->invoiceIsPaid()) {
      /** @var \Drupal\store\Entity\StoreOrder $order */
      if ($order = $invoice->getOrder()) {
        if ($order->getStatus() == StoreOrder::STATUS_NEW || $order->getStatus() == StoreOrder::STATUS_FAILED) {
          $order->setStatus(StoreOrder::STATUS_PROCESSING)->save();
          $this->salesforceSync->entityCrud($order, SalesforceSync::OPERATION_UPDATE);
        }
      }
      return $this->doPaidRedirect($invoice);
    }
    else {
      return $this->doFailedRedirect($invoice);
    }
  }

  public function cancel($invoice, $payment_method) {
    /** @var \Drupal\store\Entity\Invoice $invoice */
    $invoice = $this->entityTypeManager->getStorage('invoice')->load($invoice);
    $config = $this->config('plugin.plugin_configuration.payment_method.' . $payment_method)->get();
    /** @var \Drupal\payment\Plugin\PaymentMethod\PaymentMethodBase $plugin */
    $plugin = $this->paymentMethodManager->createInstance($payment_method, $config);
    $plugin->setInvoice($invoice)
      ->paymentCanceled();
    return $this->doCancelRedirect($invoice);
  }

  public function fail($invoice, $payment_method) {
    /** @var \Drupal\store\Entity\Invoice $invoice */
    $invoice = $this->entityTypeManager->getStorage('invoice')->load($invoice);
    $config = $this->config('plugin.plugin_configuration.payment_method.' . $payment_method)->get();
    /** @var \Drupal\payment\Plugin\PaymentMethod\PaymentMethodBase $plugin */
    $plugin = $this->paymentMethodManager->createInstance($payment_method, $config);
    $plugin->setInvoice($invoice)
      ->paymentFailed();
    return $this->doFailedRedirect($invoice);
  }

  protected function doPaidRedirect(Invoice $invoice) {
    drupal_set_message($this->t('Invoice has been paid.'));
    return $this->redirect('entity.invoice.user_view', ['invoice_number' => $invoice->id()]);
  }

  protected function doFailedRedirect(Invoice $invoice) {
    drupal_set_message($this->t('Payment failed.'), 'error');
    return $this->redirect('entity.invoice.payment', ['invoice_number' => $invoice->id()]);
  }

  protected function doCancelRedirect(Invoice $invoice) {
    drupal_set_message($this->t('You canceled the payment.'), 'warning');
    return $this->redirect('entity.invoice.payment', ['invoice_number' => $invoice->id()]);
  }

}
