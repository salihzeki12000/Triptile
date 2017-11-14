<?php

namespace Drupal\train_booking\Controller;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\payment\Plugin\PaymentMethodManager;
use Drupal\rn_user\SessionStoreFactory;
use Drupal\salesforce\SalesforceSync;
use Drupal\store\Entity\Invoice;
use Drupal\train_booking\Form\TrainBookingBaseForm;
use Drupal\train_booking\TrainBookingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\train_booking\TrainBookingLogger;

class PaymentServiceUrlController extends \Drupal\payment\Controller\PaymentServiceUrlController {

  /**
   * @var \Drupal\rn_user\SessionStore
   */
  protected $store;

  /**
   * @var \Drupal\train_booking\TrainBookingManager
   */
  protected $trainBookingManager;

  /**
   * The Train Booking Logger service.
   *
   * @var \Drupal\train_booking\TrainBookingLogger
   */
  protected $trainBookingLogger;

  /**
   * PaymentServiceUrlController constructor.
   *
   * @param \Drupal\payment\Plugin\PaymentMethodManager $payment_method_manager
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\rn_user\SessionStoreFactory $session_store_factory
   * @param \Drupal\train_booking\TrainBookingManager
   * @param \Drupal\salesforce\SalesforceSync $salesforce_sync
   * @param \Drupal\train_booking\TrainBookingLogger
   */
  public function __construct(PaymentMethodManager $payment_method_manager, EntityTypeManager $entity_type_manager, SessionStoreFactory $session_store_factory, TrainBookingManager $train_booking_manager, SalesforceSync $salesforce_sync, TrainBookingLogger $train_booking_logger) {
    parent::__construct($payment_method_manager, $entity_type_manager, $salesforce_sync);
    $this->trainBookingManager = $train_booking_manager;
    $this->store = $session_store_factory->get(TrainBookingBaseForm::COLLECTION_NAME);
    $this->trainBookingLogger = $train_booking_logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.payment.payment_method'),
      $container->get('entity_type.manager'),
      $container->get('rn_user.session_store'),
      $container->get('train_booking.train_booking_manager'),
      $container->get('salesforce_sync'),
      $container->get('train_booking.logger')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doPaidRedirect(Invoice $invoice) {
    $order = $invoice->getOrder();
    $orderHash = $order->getHash();
    $this->trainBookingManager->setStore($this->store->setSessionId($orderHash));
    $this->trainBookingManager->bookingPaid($order);
    if ($ticketIssueDate = $order->getTicketIssueDate()) {
      drupal_set_message($this->t('Your reservation is in our system. Actual train tickets are to be issued on @date.',
        ['@date' => $ticketIssueDate->format(DATETIME_DATE_STORAGE_FORMAT)], ['context' => 'DrupalMessage']));
    }
    else {
      drupal_set_message($this->t('Payment completed successfully.'));
    }
    $this->trainBookingLogger->logPaymentStatus($orderHash, 'success');
    $this->trainBookingLogger->logLastStep($orderHash, 5);
    return $this->redirect('entity.store_order.user_view', ['order_hash' => $orderHash]);
  }

  /**
   * {@inheritdoc}
   */
  protected function doFailedRedirect(Invoice $invoice) {
    $order = $invoice->getOrder();
    $orderHash = $order->getHash();
    $this->trainBookingManager->setStore($this->store->setSessionId($orderHash));
    $this->trainBookingManager->bookingFailed($order);
    drupal_set_message($this->t('Payment failed.'), 'error');
    $this->trainBookingLogger->logPaymentStatus($orderHash, 'failed');
    return $this->redirect('train_booking.payment_form', ['session_id' => $orderHash]);
  }

  /**
   * {@inheritdoc}
   */
  protected function doCancelRedirect(Invoice $invoice) {
    $order = $invoice->getOrder();
    $orderHash = $order->getHash();
    $this->trainBookingManager->setStore($this->store->setSessionId($orderHash));
    $this->trainBookingManager->bookingCanceled($order);
    drupal_set_message($this->t('You canceled the payment.'), 'warning');
    $this->trainBookingLogger->logPaymentStatus($orderHash, 'cancel');
    return $this->redirect('train_booking.payment_form', ['session_id' => $orderHash]);
  }

}
