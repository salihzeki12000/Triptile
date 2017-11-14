<?php

namespace Drupal\payment\Plugin\PaymentMethod;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Random;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\payment\Entity\Merchant;
use Drupal\payment\Entity\Transaction;
use Drupal\payment\Entity\TransactionInterface;
use Drupal\payment\Plugin\PaymentAdapter\OffSitePaymentAdapterInterface;
use Drupal\payment\Plugin\PaymentAdapter\OnSitePaymentAdapterInterface;
use Drupal\payment\Plugin\PaymentAdapter\RemoteBillingProfileAdapterInterface;
use Drupal\rn_user\Entity\User;
use Drupal\store\Entity\CustomerProfile;
use Drupal\store\Entity\Invoice;
use Drupal\store\Entity\InvoiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\salesforce\SalesforceSync;

/**
 * Base class for Payment method plugins.
 */
abstract class PaymentMethodBase extends PluginBase implements PaymentMethodInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Url
   */
  protected $successUrl;

  /**
   * @var \Drupal\Core\Url
   */
  protected $cancelUrl;

  /**
   * @var \Drupal\Core\Url
   */
  protected $failUrl;

  /**
   * @var array
   */
  protected $paymentData = [];

  /**
   * @var array
   */
  protected $billingData = [];

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * @var \Drupal\salesforce\SalesforceSync
   */
  protected $salesforceSync;

  /**
   * @var \Drupal\salesforce\Plugin\SalesforceMappingManager
   */
  protected $mappingManager;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @var string
   */
  protected $userCurrency;

  /**
   * @var \Drupal\store\Entity\InvoiceInterface
   */
  protected $invoice;

  /**
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryRepository;


  /**
   * PaymentMethodBase constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $configuration = array_merge($this->defaultConfiguration(), $configuration);
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // @todo Replace with dependency injection
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->entityQuery = \Drupal::service('entity.query');
    $this->salesforceSync = \Drupal::service('salesforce_sync');
    $this->mappingManager = \Drupal::service('plugin.manager.salesforce_mapping');
    $this->userCurrency = \Drupal::service('store.default_currency')->getUserCurrency();
    $this->request = \Drupal::request();
    $this->countryRepository = \Drupal::service('address.country_repository');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'weight' => false,
      'status' => false,
      'countries' => [],
      'include_in_top' => true, // Whether we should include the payment method in top if user country is in countries array or not
      'in_top_if_no_country' => false,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // @todo Implement
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $this->configuration['weight'],
      '#delta' => 10,
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $this->configuration['status'],
    ];

    $form['countries'] = [
      '#type' => 'select',
      '#multiple' => true,
      '#title' => $this->t('Countries'),
      '#options' => $this->countryRepository->getList(),
      '#default_value' => $this->configuration['countries'],
    ];

    $form['include_in_top'] = [
      '#type' => 'radios',
      '#title' => '',
      '#title_display' => 'none',
      '#options' => [0 => $this->t('Exclude from top if user country is in the country list'), 1 => $this->t('Include in top if user country is in the country list')],
      '#default_value' => $this->configuration['include_in_top'],
    ];

    $form['in_top_if_no_country'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include in top if user country is not defined'),
      '#default_value' => $this->configuration['in_top_if_no_country'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateConfigurationForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['weight'] = $values['weight'];
      $this->configuration['status'] = (bool) $values['status'];
      $this->configuration['countries'] = array_keys(array_filter($values['countries']));
      $this->configuration['include_in_top'] = (bool) $values['include_in_top'];
      $this->configuration['in_top_if_no_country'] = (bool) $values['in_top_if_no_country'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->configuration['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->configuration['status'];
  }

  /**
   * {@inheritdoc}
   */
  public function isTop() {
    $top = false;
    $countryCode = false;
    $ip = \Drupal::request()->getClientIp();
    if ($countryCode = \Drupal::service('master.maxmind')->getCountry($ip)) {
      if (($this->configuration['include_in_top'] && in_array($countryCode, $this->configuration['countries']))
        || (!$this->configuration['include_in_top'] && !in_array($countryCode, $this->configuration['countries']))) {
        $top = TRUE;
      }
    }
    if (!$countryCode && $this->configuration['in_top_if_no_country']) {
      $top = true;
    }

    return $top;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentDataForm(array $form, FormStateInterface $form_state, $include_title = false) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildBillingProfileForm(array $form, FormStateInterface $form_state, $include_title = false) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaymentDataForm(array $form, FormStateInterface $form_state) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function validateBillingProfileForm(array $form, FormStateInterface $form_state) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaymentDataForm(array $form, FormStateInterface $form_state) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function submitBillingProfileForm(array $form, FormStateInterface $form_state) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSuccessUrl(Url $success_url) {
    $this->successUrl = $success_url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCancelUrl(Url $cancel_url) {
    $this->cancelUrl = $cancel_url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFailUrl(Url $fail_url) {
    $this->failUrl = $fail_url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setInvoice(InvoiceInterface $invoice) {
    $this->invoice = $invoice;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInvoice() {
    if ($this->invoice) {
      return $this->invoice;
    }
    throw new \InvalidArgumentException('Invoice wasn\'t provided.');
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentData(array $payment_data) {
    $this->paymentData = $payment_data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingData(array $billing_data) {
    $this->billingData = $billing_data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function processPayment() {
    $url = $this->failUrl;
    $this->createBillingProfile();

    foreach ($this->getMerchants() as $merchant) {
      try {
        $payment_adapter = $merchant->getPaymentAdapterPlugin();
        $transaction = $this->createTransaction($merchant);

        if ($payment_adapter instanceof OnSitePaymentAdapterInterface) {
          $payment_adapter->doPayment($transaction, $this->paymentData, $this->billingData);
          if (in_array($transaction->getStatus(), [Transaction::STATUS_PENDING, Transaction::STATUS_SUCCESS])) {
            $url = $this->successUrl;
          }
        }

        if ($payment_adapter instanceof OffSitePaymentAdapterInterface) {
          $payment_adapter->setSuccessUrl($this->successUrl)
            ->setCancelUrl($this->cancelUrl)
            ->setFailUrl($this->failUrl);
          if ($payment_adapter->initPayment($transaction, $this->paymentData, $this->billingData)) {
            $url = $payment_adapter->getPaymentUrl();
          }
        }

        $this->updateInvoiceStatus($transaction);
      }
      catch (\Exception $e) {
        watchdog_exception('payment', $e);
      }

      if (isset($transaction)) {
        $transaction->save();
      }

      if (in_array($this->getInvoice()->getStatus(), [Invoice::STATUS_CLEARING, Invoice::STATUS_PAID])) {
        // exit if transaction has been processed successfully
        break;
      }
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function paymentReturned() {
    if ($this->getInvoice()->getStatus() == Invoice::STATUS_CLEARING && $transaction = $this->getLatestTransaction()) {
      try {
        $adapter = $transaction->getMerchant()
          ->getPaymentAdapterPlugin();
        if ($adapter instanceof OffSitePaymentAdapterInterface) {
          $adapter->completePayment($transaction);
        }
        if ($adapter instanceof OnSitePaymentAdapterInterface && $transaction->getStatus() == Transaction::STATUS_PENDING) {
          $adapter->syncTransactionStatus($transaction);
        }
        if ($adapter instanceof RemoteBillingProfileAdapterInterface) {
          $this->setBillingData($adapter->getBillingProfileData($transaction))
            ->createBillingProfile();
        }

        $transaction->save();
        $this->updateInvoiceStatus($transaction);
      }
      catch (\Exception $e) {
        watchdog_exception('payment', $e);
      }
    }
    $this->exportInvoiceToSalesforce();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function paymentCanceled() {
    if ($transaction = $this->getLatestTransaction()) {
      $this->updateTransaction($transaction);
      $this->updateInvoiceStatus($transaction);
    }
    $this->exportInvoiceToSalesforce();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function paymentFailed() {
    if($transaction = $this->getLatestTransaction()) {
      $this->updateTransaction($transaction);
      $this->updateInvoiceStatus($transaction);
    }
    $this->exportInvoiceToSalesforce();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function invoiceIsPaid() {
    $paid = false;
    if ($this->getInvoice()->getStatus() == Invoice::STATUS_PAID) {
      $paid = true;
    }
    elseif($transaction = $this->getLatestTransaction()) {
      if ($transaction->getStatus() == Transaction::STATUS_PENDING) {
        $this->updateTransaction($transaction);
      }
      $this->updateInvoiceStatus($transaction);
      $paid = in_array($this->getInvoice()->getStatus(), [Invoice::STATUS_CLEARING, Invoice::STATUS_PAID]);
    }

    return $paid;
  }

  /**
   * {@inheritdoc}
   */
  public function processTransactionUpdateRequest(TransactionInterface $transaction, Request $request) {
    $transaction->getMerchant()
      ->getPaymentAdapterPlugin()
      ->processTransactionUpdateRequest($transaction, $request);
    $transaction->save();

    $this->updateInvoiceStatus($transaction);
    $this->exportInvoiceToSalesforce();

    return $this;
  }

  /**
   * Loads merchants for the current payment method.
   *
   * @return \Drupal\payment\Entity\Merchant[]
   */
  protected function getMerchants() {
    $merchantRouterRules = $this->entityTypeManager->getStorage('merchant_router_rule')->loadMultiple();
    usort($merchantRouterRules, function($a, $b) {
      if ($a->getWeight() < $b->getWeight()) {
        return -1;
      }
      elseif ($a->getWeight() > $b->getWeight()) {
        return 1;
      }
      return 0;
    });

    $vars = $this->getMerchantRouterVariables();
    $merchants = [];
    if (!empty($merchantRouterRules)) {
      $merchantIds = [];
      /** @var \Drupal\payment\Entity\MerchantRouterRule $merchantRouterRule */
      foreach ($merchantRouterRules as $merchantRouterRule) {
        if ($merchantRouterRule->isApplicable($vars)) {
          $merchantIds = $merchantRouterRule->getMerchantIds();
          break;
        }
      }
      $allMerchants = empty($merchantIds) ? [] : $this->entityTypeManager->getStorage('merchant')->loadMultiple($merchantIds);
    }
    else {
      $allMerchants = $this->entityTypeManager->getStorage('merchant')->loadMultiple();
    }

    /** @var \Drupal\payment\Entity\Merchant $merchant */
    foreach ($allMerchants as $merchant) {
      if ($merchant->isEnabled() && in_array($this->getBaseId(), $merchant->getPaymentMethods())) {
        $merchants[] = $merchant;
      }
    }


    return $merchants;
  }

  /**
   * Gets variables for merchant router from context and invoice.
   *
   * @return array
   */
  private function getMerchantRouterVariables() {
    $billingProfile = $this->getInvoice()->getCustomerProfile();
    $vars = [
      'invoice_currency' => $this->getInvoice()->getAmount()->getCurrencyCode(),
      'user_currency' => $this->userCurrency,
      'ip_country' => \Drupal::service('master.maxmind')->getCountry($this->request->getClientIp()),
      'billing_country' => $billingProfile ? $billingProfile->getAddress()->getCountryCode() : '',
      'payment_method' => $this->getBaseId(),
      'card_type' => isset($this->paymentData['card_type']) ? $this->paymentData['card_type'] : false,
    ];
    return $vars;
  }

  /**
   * Creates new transaction and sets default values.
   *
   * @param \Drupal\payment\Entity\Merchant $merchant
   * @return \Drupal\payment\Entity\Transaction
   */
  protected function createTransaction(Merchant $merchant) {
    /** @var \Drupal\payment\Entity\Transaction $transaction */
    $transaction = Transaction::create()
      ->setMerchant($merchant)
      ->setOriginalAmount($this->getInvoice()->getAmount())
      ->setType(Transaction::TYPE_PAYMENT)
      ->setPaymentMethod($this->getBaseId())
      ->setInvoice($this->getInvoice());
    $transaction->save();

    return $transaction;
  }

  /**
   * Maps transactions status to invoice. Saves invoice if status changes.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return static
   */
  protected function updateInvoiceStatus(TransactionInterface $transaction) {
    $old_status = $this->getInvoice()->getStatus();
    switch ($transaction->getStatus()) {
      case Transaction::STATUS_SUCCESS:
        $this->getInvoice()->setStatus(Invoice::STATUS_PAID);
        break;
      case Transaction::STATUS_PENDING:
        $this->getInvoice()->setStatus(Invoice::STATUS_CLEARING);
        break;
      case Transaction::STATUS_FAILED:
        $this->getInvoice()->setStatus(Invoice::STATUS_UNPAID);
        break;
    }

    if ($this->getInvoice()->getStatus() != $old_status) {
      $this->getInvoice()->save();
    }

    return $this;
  }

  /**
   * Loads latest created transaction on the invoice.
   *
   * @return \Drupal\payment\Entity\Transaction|null
   */
  protected function getLatestTransaction() {
    $transaction = null;
    $ids = $this->entityQuery->get('transaction', 'AND')
      ->condition('invoice.target_id', $this->getInvoice()->id())
      ->condition('type', Transaction::TYPE_PAYMENT)
      ->condition('payment_method', $this->getBaseId())
      ->execute();
    if (!empty($ids)) {
      $transaction = $this->entityTypeManager->getStorage('transaction')
        ->load(max($ids));
    }

    return $transaction;
  }

  /**
   * Gets all transactions for the invoice.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  protected function getTransactions() {
    $transactions = $this->entityTypeManager->getStorage('transaction')
        ->loadByProperties(['invoice.target_id' => $this->getInvoice()->id()]);

    return $transactions;
  }

  /**
   * Creates customer billing profile.
   *
   * @return static
   */
  protected function createBillingProfile() {
    if (!empty($this->billingData)) {
      // If there is no user with the specified email then set invoice owner as
      // new customer profile owner.
      $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $this->billingData['email']]);
      if (!$user = reset($users)) {
        $user = $this->getInvoice()->getUser();
      }
      if (!$user) {
        $random = new Random();
        $user = User::create([
          'name' => $this->billingData['email'],
          'mail' => $this->billingData['email'],
          'pass' => $random->string(8),
          'status' => 1,
        ]);
      }
      /** @var \Drupal\store\Entity\CustomerProfile $customerProfile */
      $customerProfile = CustomerProfile::create()
        ->setInvoice($this->getInvoice())
        ->setAddress($this->billingData)
        ->setEmail($this->billingData['email'])
        ->setOwner($user);
      if (!empty($this->billingData['phone_number'])) {
        $customerProfile->setPhoneNumber($this->billingData['phone_number']);
      }
      $customerProfile->save();
    }

    return $this;
  }

  /**
   * Trigger synchronize action 'PUSH' for entities.
   *
   */
  protected function exportInvoiceToSalesforce() {
    $this->salesforceSync->entityCrud($this->getInvoice(), SalesforceSync::OPERATION_UPDATE);
    foreach ($this->getTransactions() as $transaction) {
      $this->salesforceSync->entityCrud($transaction, SalesforceSync::OPERATION_UPDATE);
    }
  }

  /**
   * Updates transaction status via related payment adapter.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   */
  protected function updateTransaction(TransactionInterface $transaction) {
    $oldStatus = $transaction->getStatus();
    try {
      $transaction->getMerchant()
        ->getPaymentAdapterPlugin()
        ->syncTransactionStatus($transaction);
    }
    catch (\Exception $exception) {
      watchdog_exception('payment', $exception);
    }
    if ($transaction->getStatus() != $oldStatus) {
      $transaction->save();
    }
  }

}
