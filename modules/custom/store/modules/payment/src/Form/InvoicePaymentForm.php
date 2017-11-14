<?php

namespace Drupal\payment\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\payment\Plugin\PaymentMethodManager;
use Drupal\store\Entity\Invoice;
use Drupal\store\DefaultCurrency;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PaymentForm.
 *
 * @package Drupal\payment\Form
 */
class InvoicePaymentForm extends FormBase {

  use PaymentFormTrait {
    configFactory as traitConfigFactory;
  }

  /**
   * The Default Currency service.
   *
   * @var \Drupal\store\DefaultCurrency
   */
  protected $defaultCurrency;

  /**
   * PaymentForm constructor.
   *
   * @param \Drupal\payment\Plugin\PaymentMethodManager $payment_method_manager
   * @param \Drupal\store\DefaultCurrency $default_currency
   */
  public function __construct(PaymentMethodManager $payment_method_manager, DefaultCurrency $default_currency) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->defaultCurrency = $default_currency;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.payment.payment_method'),
      $container->get('store.default_currency')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Invoice $invoice_number = NULL) {
    $invoice = $invoice_number;
    // If invoice can't be paid, redirect to invoice page.
    if (!$invoice->isPayable()) {
      return $this->redirect('entity.invoice.user_view', ['invoice_number' => $invoice->id()]);
    }

    $form['#attributes']['class'][] = 'invoice-payment-form';

    $form['sidebar'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['sidebar']],
      '#weight' => 1,
    ];
    $form['sidebar']['container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['order-details-wrapper', 'container', 'sidebar-container', 'invoice-sidebar-wrapper']],
    ];
    $form['sidebar']['container']['title'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['order-details', 'sidebar-item', 'invoice-title', 'sidebar-label']],
      '#markup' => t('Invoice @invoice', array('@invoice' => $invoice->getInvoiceNumber())),
    ];

    $form['sidebar']['container']['note'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['order-details', 'sidebar-item', 'invoice-note', 'last']],
      '#markup' => $invoice->getDescription(),
    ];

    $form['sidebar']['total_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['order-details-wrapper', 'invoice-total-wrapper', 'total-order', 'container']],
    ];

    $form['sidebar']['total_container']['grand_total'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['order-details', 'sidebar-item', 'sidebar-label', 'order-total', 'last', 'invoice-grand-total']],
    ];

    $form['sidebar']['total_container']['grand_total']['label'] = [
      '#type' => 'container',
      '#markup' => t('Grand total'),
    ];
    $currencyCode = $this->defaultCurrency->getUserCurrency();
    $form['sidebar']['total_container']['grand_total']['value'] = [
      '#type' => 'container',
      '#markup' => $invoice->getAmount()->convert($currencyCode),
    ];
    $expirationDate = $invoice->getExpirationDate();
    $currentDate = new DrupalDateTime();

    if(!empty($expirationDate) && ($expirationDate > $currentDate)) {
      $form['sidebar']['note_container'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['order-details-wrapper', 'invoice-note-wrapper', 'container']],
      ];

      $form['sidebar']['note_container']['grand_total'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['expiration-note']],
        '#markup' => t('We appreciate your payment of the invoice by @date.', ['@date' => $invoice->getExpirationDate()->format('M jS, Y')]),
      ];
    }

    $mainContainer = [
      '#type' => 'container',
      '#attributes' => ['class' => ['main']],
      '#weight' => 0,
    ];
    $form['main'] = array_merge($mainContainer, $this->buildPaymentForm([], $form_state, $invoice));

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->validatePaymentForm($form['main'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $invoice = $form_state->get('invoice');
    $plugin = $this->getPaymentMethodPlugin($form_state->getValue('payment_method'), $invoice);
    $route_params = [
      'invoice' => $invoice->id(),
      'payment_method' => $plugin->getBaseId(),
    ];
    $this->setSuccessUrl(Url::fromRoute('entity.invoice.payment.success', $route_params))
      ->setCancelUrl(Url::fromRoute('entity.invoice.payment.cancel', $route_params))
      ->setFailUrl(Url::fromRoute('entity.invoice.payment.fail', $route_params));

    $this->submitPaymentForm($form['main'], $form_state);
  }

}
