<?php

namespace Drupal\payment\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\payment\Plugin\PaymentAdapter\RefundAdapterInterface;
use Drupal\master\EntityWithDataPropertyInterface;
use Drupal\salesforce\Entity\MappableEntityInterface;
use Drupal\salesforce\Entity\MappableEntityTrait;
use Drupal\store\Entity\InvoiceInterface;
use Drupal\master\EntityWithDataPropertyTrait;
use Drupal\store\Price;

/**
 * Defines the Transaction entity.
 *
 * @ingroup store
 *
 * @ContentEntityType(
 *   id = "transaction",
 *   label = @Translation("Transaction"),
 *   handlers = {
 *     "view_builder" = "Drupal\payment\TransactionViewBuilder",
 *     "list_builder" = "Drupal\payment\TransactionListBuilder",
 *     "views_data" = "Drupal\payment\Entity\TransactionViewsData",
 *     "form" = {
 *       "default" = "Drupal\payment\Form\Admin\TransactionForm",
 *       "add" = "Drupal\payment\Form\Admin\TransactionForm",
 *       "edit" = "Drupal\payment\Form\Admin\TransactionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\payment\Entity\TransactionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\payment\Entity\TransactionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "transaction",
 *   admin_permission = "administer transaction entities",
 *   revision_table = "transaction_revision",
 *   revision_data_table = "transaction_field_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/store/transaction/{transaction}",
 *     "collection" = "/admin/store/transaction",
 *     "edit-form" = "/admin/store/transaction/{transaction}/edit",
 *     "delete-form" = "/admin/store/transaction/{transaction}/delete"
 *   },
 *   field_ui_base_route = "entity.transaction.settings",
 *   settings_form = "Drupal\payment\Form\Admin\TransactionSettingsForm"
 * )
 *
 * @todo Disallow edit/delete transactions.
 */
class Transaction extends ContentEntityBase implements TransactionInterface, MappableEntityInterface, EntityWithDataPropertyInterface {

  use EntityChangedTrait;
  use MappableEntityTrait;
  use EntityWithDataPropertyTrait;

  /**
   * Transaction statuses.
   */
  const
    STATUS_SUCCESS = 'success',
    STATUS_FAILED  = 'failed',
    STATUS_PENDING = 'pending',
    STATUS_REFUNDED = 'refunded',
    STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

  /**
   * Transaction statuses.
   */
  const
    TYPE_PAYMENT = 1,
    TYPE_REFUND  = 2;

  /**
   * Gets a status name or array of all status names keyed by it's value.
   *
   * @param string $status
   * @return array|string
   */
  public static function getStatusName($status = '') {
    $names = [
      static::STATUS_SUCCESS => t('Success'),
      static::STATUS_FAILED => t('Failed'),
      static::STATUS_PENDING => t('Pending'),
      static::STATUS_REFUNDED => t('Refunded'),
      static::STATUS_PARTIALLY_REFUNDED => t('Partially refunded'),
    ];

    return isset($names[$status]) ? $names[$status] : $names;
  }

  /**
   * Gets a types name or array of all status names keyed by it's value.
   *
   * @param integer $type
   * @return array|string
   */
  public static function getTypesName($type = 0) {
    $names = [
      static::TYPE_PAYMENT => t('Payment'),
      static::TYPE_REFUND => t('Refund'),
    ];

    return isset($names[$type]) ? $names[$type] : $names;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // set owner
    if (empty($this->getOwner())) {
      $this->set('uid', \Drupal::currentUser()->id());
    }

    // set ip address
    if (empty($this->getIPAddress())) {
      $this->set('ip_address', \Drupal::request()->getClientIp());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['merchant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Merchant id'))
      ->setDescription(t('A merchant used to process the transaction.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'merchant')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['remote_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remote transaction id'))
      ->setDescription(t('ID of the transaction in the payment system.'))
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The payment method used to create transaction.'))
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Status.'))
      ->setRevisionable(TRUE)
      ->setSettings(['allowed_values' => static::getStatusName()])
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['remote_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment system status'))
      ->setDescription(t('Payment system status.'))
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Transaction type'))
      ->setDescription(t('Transaction type.'))
      ->setRevisionable(TRUE)
      ->setSettings(['allowed_values' => static::getTypesName()])
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent transaction id'))
      ->setDescription(t('Parent transaction id.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'transaction')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invoice'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Invoice'))
      ->setDescription(t('Invoice.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'invoice')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['amount'] = BaseFieldDefinition::create('price')
      ->setLabel(t('Amount'))
      ->setDescription(t('Amount.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'price_default',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'price_default',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['original_amount'] = BaseFieldDefinition::create('price')
      ->setLabel(t('Original amount'))
      ->setDescription(t('Original Amount.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'price_default',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'price_default',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['currency_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Currency rate'))
      ->setDescription(t('Currency rate.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP address'))
      ->setDescription(t('IP Address.'))
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -7,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -7,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('Reference to user.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Message'))
      ->setDescription(t('Used as a simplified log to indicate success/fail on each step.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['log'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Log'))
      ->setDescription(t('All requests to a payment system, its responses and possible requests to a site endpoint should be stored here.'));

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('Some data related to the transaction that can\'t be stored in a field.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setRevisionable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getMerchant() {
    return $this->get('merchant_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setMerchant(MerchantInterface $merchant) {
    $this->set('merchant_id', $merchant->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethod() {
    return $this->get('payment_method')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentMethod($payment_method) {
    $this->set('payment_method', $payment_method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteStatus() {
    return $this->get('remote_status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRemoteStatus($status) {
    $this->set('remote_status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->set('type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentTransaction() {
    return $this->get('parent')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentTransaction(TransactionInterface $transaction) {
    $this->set('parent', $transaction->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInvoice() {
    return $this->get('invoice')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setInvoice(InvoiceInterface $invoice) {
    $this->set('invoice', $invoice->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    if (!$this->get('amount')->isEmpty()) {
      return $this->get('amount')->first()->toPrice();
    }

    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount(Price $amount) {
    $this->set('amount', $amount);
    $this->setCurrencyRate();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalAmount() {
    if (!$this->get('original_amount')->isEmpty()) {
      return $this->get('original_amount')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalAmount(Price $amount) {
    $this->set('original_amount', $amount);
    $this->setCurrencyRate();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrencyRate() {
    return $this->get('currency_rate')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getIPAddress() {
    return $this->get('ip_address')->value;
  }

  // TODO Don't define setUser method. It sets user automatically in presave
  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->get('message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function appendMessage($message) {
    $message = $this->getMessage() . "\n" . $message;
    $this->set('message', $message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLog() {
    return !empty($this->get('log')->first()) ? $this->get('log')->first()->getValue() : [];
  }

  /**
   * {@inheritdoc}
   */
  public function appendLog($log) {
    $log = array_merge($this->getLog(), [$log]);
    $this->set('log', [$log]);
    return $this;
  }

  /**
   * Calculates currency rate for the transaction.
   */
  protected function setCurrencyRate() {
    $amount = $this->getAmount();
    $original_amount = $this->getOriginalAmount();
    if (!empty($amount) && !empty($original_amount)) {
      $this->set('currency_rate', \Drupal::service('currency.exchange_rate_provider')->load($original_amount->getCurrencyCode(), $amount->getCurrencyCode())->getRate());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteId() {
    return $this->get('remote_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRemoteId($remote_id) {
    $this->set('remote_id', $remote_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSuccess() {
    return $this->getStatus() === static::STATUS_SUCCESS;
  }

  /**
   * {@inheritdoc}
   */
  public function isRefundable() {
    if ($this->getMerchant()) {
      $adapter = $this->getMerchant()->getPaymentAdapterPlugin();
      return in_array($this->getStatus(), [static::STATUS_SUCCESS, static::STATUS_PARTIALLY_REFUNDED])
        && $this->getType() == static::TYPE_PAYMENT
        && $this->getRefundableAmount()->getNumber() > 0
        && $adapter instanceof RefundAdapterInterface
        && $adapter->isTransactionRefundable($this);
    }
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function getRefundableAmount() {
    $refundableAmount = $this->getAmount();
    foreach ($this->getChildTransactions() as $transaction) {
      if ($transaction->getType() == static::TYPE_REFUND && in_array($transaction->getStatus(), [static::STATUS_PENDING, static::STATUS_SUCCESS])) {
        $refundableAmount = $refundableAmount->add($transaction->getAmount());
      }
    }

    return $refundableAmount;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildTransactions() {
    $transactions = [];
    if (!$this->isNew()) {
      $transactions = $this->entityTypeManager()->getStorage('transaction')
        ->loadByProperties(['parent.target_id' => $this->id()]);
    }

    return $transactions;
  }

}
