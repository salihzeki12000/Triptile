<?php

namespace Drupal\payment\Form\Admin;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\currency\FormHelper;
use Drupal\currency\PluginBasedExchangeRateProvider;
use Drupal\payment\Entity\Transaction;
use Drupal\payment\Entity\TransactionInterface;
use Drupal\payment\Plugin\PaymentMethodManager;
use Drupal\salesforce\SalesforceSync;
use Drupal\store\Entity\Invoice;
use Drupal\store\Entity\InvoiceInterface;
use Drupal\store\Price;
use Drupal\store\PriceFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransactionRefundForm extends FormBase {

  /**
   * @var \Drupal\store\PriceFactory
   */
  protected $priceFactory;

  /**
   * @var \Drupal\currency\FormHelper
   */
  protected $currencyFormHelper;

  /**
   * @var \Drupal\currency\PluginBasedExchangeRateProvider
   */
  protected $exchangeRateProvider;

  /**
   * @var \Drupal\payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * @var \Drupal\salesforce\SalesforceSync
   */
  protected $salesforceSync;

  /**
   * TransactionRefundForm constructor.
   *
   * @param \Drupal\store\PriceFactory $price_factory
   * @param \Drupal\currency\FormHelper $currency_form_helper
   * @param \Drupal\currency\PluginBasedExchangeRateProvider $exchange_rate_provider
   * @param \Drupal\payment\Plugin\PaymentMethodManager $payment_method_manager
   * @param \Drupal\Core\Session\AccountProxy $account_proxy
   */
  public function __construct(PriceFactory $price_factory, FormHelper $currency_form_helper, PluginBasedExchangeRateProvider $exchange_rate_provider, PaymentMethodManager $payment_method_manager, AccountProxy $account_proxy, SalesforceSync $salesforce_sync) {
    $this->priceFactory = $price_factory;
    $this->currencyFormHelper = $currency_form_helper;
    $this->exchangeRateProvider = $exchange_rate_provider;
    $this->paymentMethodManager = $payment_method_manager;
    $this->currentUser = $account_proxy;
    $this->salesforceSync = $salesforce_sync;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('store.price'),
      $container->get('currency.form_helper'),
      $container->get('currency.exchange_rate_provider'),
      $container->get('plugin.manager.payment.payment_method'),
      $container->get('current_user'),
      $container->get('salesforce_sync')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_transaction_refund';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, TransactionInterface $transaction = null) {
    $triggeringElement = $form_state->getTriggeringElement();
    $form = empty($triggeringElement) ? $this->firstStep($form, $form_state, $transaction) : $this->secondStep($form, $form_state, $transaction);

    $form_state->set('transaction', $transaction);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    /** @var \Drupal\payment\Entity\Transaction $originalTransaction */
    $transaction = $form_state->get('transaction');
    $values = $form_state->getValues();
    $refundAmount = $values['full_refund'] ? $transaction->getRefundableAmount() : $this->priceFactory->get($values['number'], $values['currency_code']);
    if (!$refundAmount->lessThanOrEqual($transaction->getRefundableAmount())) {
      $amount = new FormattableMarkup( $transaction->getRefundableAmount(), []);
      $form_state->setError($form['amount']['number'], $this->t('It is not possible to refund more than @max.', ['@max' => $amount]));
      $form_state->setError($form['amount']['currency_code']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\payment\Entity\Transaction $originalTransaction */
    $originalTransaction = $form_state->get('transaction');
    // TODO All this logic should go to the refund service.
    /** @var \Drupal\payment\Plugin\PaymentAdapter\RefundAdapterInterface $adapter */
    $adapter = $originalTransaction->getMerchant()->getPaymentAdapterPlugin();
    $values = $form_state->getValues();
    $refundAmount = $values['full_refund'] ? null : $this->priceFactory->get($values['number'], $values['currency_code']);
    $refundInvoice = $this->createRefundInvoice($originalTransaction, $refundAmount);
    $refundTransaction = $this->createRefundTransaction($originalTransaction, $refundInvoice);
    if ($adapter->processRefund($originalTransaction, $refundTransaction)) {
      drupal_set_message($this->t('Refund for transaction @id processed successfully.', ['@id' => $originalTransaction->id()]));
      $refundInvoice->setStatus(Invoice::STATUS_PAID);
      if ($originalTransaction->isRefundable()) {
        $originalTransaction->setStatus(Transaction::STATUS_PARTIALLY_REFUNDED);
      }
      else {
        $originalTransaction->setStatus(Transaction::STATUS_REFUNDED);
      }
      $originalTransaction->save();
      $this->salesforceSync->entityCrud($originalTransaction, SalesforceSync::OPERATION_UPDATE);
    }
    else {
      drupal_set_message($this->t('Refund for transaction @id failed.', ['@id' => $originalTransaction->id()]), 'error');
    }
    $refundTransaction->save();
    $refundInvoice->save();
    $this->salesforceSync->entityCrud($refundInvoice, SalesforceSync::OPERATION_UPDATE);
    $this->salesforceSync->entityCrud($refundTransaction, SalesforceSync::OPERATION_UPDATE);

    $form_state->setRedirectUrl($refundTransaction->toUrl());
  }

  /**
   * Displays confirmation form.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function ajaxDisplayConfirmation($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#payment-transaction-refund', $form));
    return $response;
  }

  /**
   * First step form.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\payment\Entity\TransactionInterface|NULL $transaction
   * @return array
   */
  protected function firstStep($form, FormStateInterface $form_state, TransactionInterface $transaction = null) {
    /** @var \Drupal\payment\Plugin\PaymentAdapter\RefundAdapterInterface $adapter */
    $adapter = $transaction->getMerchant()->getPaymentAdapterPlugin();

    $form['full_refund'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Full refund'),
      '#default_value' => true,
      '#attributes' => ['class' => ['full-refund']],
    ];

    if ($adapter->supportsPartialRefund($transaction)) {
      $form['amount'] = [
        '#type' => 'fieldset',
        '#states' => [
          'visible' => [
            ':input.full-refund' => ['checked' => false],
          ],
        ],
      ];

      $form['amount']['number'] = [
        '#type' => 'number',
        '#step' => 0.01,
        '#title' => $this->t('Amount to refund'),
        '#default_value' => $transaction->getRefundableAmount()->convert($transaction->getOriginalAmount()->getCurrencyCode())->getNumber(),
      ];

      $currency_options = $this->currencyFormHelper->getCurrencyOptions();
      unset($currency_options['XXX']);

      $form['amount']['currency_code'] = [
        '#type' => 'select',
        '#options' => $currency_options,
        '#default_value' => $transaction->getOriginalAmount()->getCurrencyCode(),
      ];
    }
    else {
      $form['full_refund']['#disabled'] = true;
    }

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['actions']],
    ];
    $form['actions']['refund'] = [
      '#type' => 'button',
      '#value' => $this->t('Refund'),
      '#name' => 'refund',
      '#ajax' => [
        'callback' => [$this, 'ajaxDisplayConfirmation'],
      ],
    ];

    $url = null;
    if ($this->getRequest()->query->has('destination')) {
      $options = UrlHelper::parse($this->getRequest()->query->get('destination'));
      try {
        $url = Url::fromUserInput('/' . ltrim($options['path'], '/'), $options);
      }
      catch (\InvalidArgumentException $e) {
        // Suppress the exception and use link to list of transactions.
      }
    }
    // Check for a route-based cancel link.
    if (!$url) {
      $url = Url::fromRoute('entity.transaction.collection');
    }
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $url,
      '#cache' => ['contexts' => ['url.query_args:destination']],
    ];

    return $form;
  }

  /**
   * Second step form.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\payment\Entity\TransactionInterface|NULL $transaction
   */
  protected function secondStep($form, FormStateInterface $form_state, TransactionInterface $transaction = null) {
    $values = $form_state->getValues();
    $form['full_refund'] = [
      '#type' => 'value',
      '#value' => $values['full_refund']
    ];
    if (!$values['full_refund']) {
      $form['number'] = [
        '#type' => 'value',
        '#value' => $values['number']
      ];
      $form['currency_code'] = [
        '#type' => 'value',
        '#value' => $values['currency_code']
      ];
    }

    $form['summary'] = $this->getSummaryTable($form_state, $transaction);

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['actions']],
    ];
    $form['actions']['confirm'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm refund'),
    ];

    $url = Url::fromRoute('payment.refund_transaction', ['transaction' => $transaction->id()]);
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $url,
    ];


    return $form;

  }

  /**
   * Generates renderable array for summary table.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return array
   */
  protected function getSummaryTable(FormStateInterface $form_state, TransactionInterface $transaction) {
    $table = [
      '#type' => 'table',
      '#header' => ['', $this->t('Original'), $this->t('Real'), $this->t('Currency rate')],
      '#id' => 'refund-summary-table',
    ];
    $table[] = [
      'info' => ['#markup' => $this->t('Transaction')],
      'original' => ['#markup' => (string) $transaction->getOriginalAmount()],
      'real' => ['#markup' => (string) $transaction->getAmount()],
      'currency_rate' => ['#plain_text' => $transaction->getCurrencyRate()],
    ];

    foreach ($transaction->getChildTransactions() as $childTransaction) {
      if ($childTransaction->getType() == Transaction::TYPE_REFUND && in_array($childTransaction->getStatus(), [Transaction::STATUS_SUCCESS, Transaction::STATUS_PENDING])) {
        $table[] = [
          'info' => ['#markup' => $this->t('Processed refund')],
          'original' => ['#markup' => (string) $childTransaction->getOriginalAmount()],
          'real' => ['#markup' => (string) $childTransaction->getAmount()],
          'currency_rate' => ['#plain_text' => $childTransaction->getCurrencyRate()],
        ];
      }
    }

    $table[] = [
      'info' => ['#markup' => $this->t('Current refund')],
      'original' => ['#markup' => $this->getOriginalRefund($form_state, $transaction)],
      'real' => ['#markup' => $this->getRealRefund($form_state, $transaction)],
      'currency_rate' => ['#plain_text' => $this->getRefundCurrencyRate($form_state, $transaction)],
    ];

    return $table;
  }

  /**
   * Gets original amount of refund.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return \Drupal\store\Price
   */
  protected function getOriginalRefund(FormStateInterface $form_state, TransactionInterface $transaction) {
    $originalRefund = $transaction->getRefundableAmount()->convert($transaction->getOriginalAmount()->getCurrencyCode());
    if (!$form_state->getValue('full_refund') && $form_state->hasValue('number') && $form_state->hasValue('currency_code')) {
      $originalRefund = $this->priceFactory->get($form_state->getValue('number'), $form_state->getValue('currency_code'));
    }

    return $originalRefund->multiply(-1);
  }

  /**
   * Gets real refund amount.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return \Drupal\store\Price
   */
  protected function getRealRefund(FormStateInterface $form_state, TransactionInterface $transaction) {
    return $this->getOriginalRefund($form_state, $transaction)->convert($transaction->getAmount()->getCurrencyCode());
  }

  /**
   * Gets current currency rate which will be used to calculate real refund
   * amount.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return string
   */
  protected function getRefundCurrencyRate(FormStateInterface $form_state, TransactionInterface $transaction) {
    $originalRefund = $this->getOriginalRefund($form_state, $transaction);
    $realRefund = $this->getRealRefund($form_state, $transaction);
    return $this->exchangeRateProvider->load($originalRefund->getCurrencyCode(), $realRefund->getCurrencyCode())->getRate();
  }

  /**
   * Creates refund invoice.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $originalTransaction
   * @param \Drupal\store\Price|NULL $refundAmount
   * @return \Drupal\store\Entity\InvoiceInterface
   */
  protected function createRefundInvoice(TransactionInterface $originalTransaction, Price $refundAmount = null) {
    $refundAmount = $refundAmount ?: $originalTransaction->getRefundableAmount()->convert($originalTransaction->getOriginalAmount()->getCurrencyCode());
    $originalInvoice = $originalTransaction->getInvoice();
    $refundInvoice = Invoice::create()
      ->setUser($originalInvoice->getUser())
      ->setOrder($originalInvoice->getOrder())
      ->setAmount($refundAmount->multiply(-1))
      ->setStatus(Invoice::STATUS_UNPAID)
      ->setExpirationDate(new DrupalDateTime())
      ->setVisibility(1);
    $refundInvoice->save();

    return $refundInvoice;
  }

  /**
   * Creates refund transaction.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $originalTransaction
   * @param \Drupal\store\Entity\InvoiceInterface $refundInvoice
   * @return \Drupal\payment\Entity\TransactionInterface
   */
  protected function createRefundTransaction(TransactionInterface $originalTransaction, InvoiceInterface $refundInvoice) {
    $refundTransaction = Transaction::create()
      ->setMerchant($originalTransaction->getMerchant())
      ->setPaymentMethod($originalTransaction->getPaymentMethod())
      ->setStatus(Transaction::STATUS_PENDING)
      ->setType(Transaction::TYPE_REFUND)
      ->setParentTransaction($originalTransaction)
      ->setInvoice($refundInvoice)
      ->setOriginalAmount($refundInvoice->getAmount())
      ->setAmount($refundInvoice->getAmount()->convert($originalTransaction->getAmount()->getCurrencyCode()));
    $refundTransaction->save();
    return $refundTransaction;
  }

}
