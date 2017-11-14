<?php

namespace Drupal\store\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\payment\Entity\Transaction;
use Drupal\salesforce\Entity\MappableEntityInterface;
use Drupal\salesforce\Entity\MappableEntityTrait;
use Drupal\store\Price;
use Drupal\user\UserInterface;

/**
 * Defines the Invoice entity.
 *
 * @ingroup store
 *
 * @ContentEntityType(
 *   id = "invoice",
 *   label = @Translation("Invoice"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\store\InvoiceListBuilder",
 *     "views_data" = "Drupal\store\Entity\InvoiceViewsData",
 *     "form" = {
 *       "default" = "Drupal\store\Form\InvoiceForm",
 *       "add" = "Drupal\store\Form\InvoiceForm",
 *       "edit" = "Drupal\store\Form\InvoiceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "invoice",
 *   data_table = "invoice",
 *   revision_table = "invoice_revision",
 *   revision_data_table = "invoice_field_revision",
 *   admin_permission = "administer invoice entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "number",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/store/invoice/{invoice}",
 *     "add-form" = "/admin/store/invoice/add",
 *     "edit-form" = "/admin/store/invoice/{invoice}/edit",
 *     "delete-form" = "/admin/store/invoice/{invoice}/delete",
 *     "collection" = "/admin/store/invoice",
 *   },
 *   field_ui_base_route = "entity.invoice.settings",
 *   settings_form = "Drupal\store\Form\InvoiceSettingsForm"
 * )
 */
class Invoice extends ContentEntityBase implements InvoiceInterface, MappableEntityInterface {

  use EntityChangedTrait;
  use MappableEntityTrait;

  /**
   * Invoice statuses.
   */
  const
    STATUS_UNPAID = 'unpaid',
    STATUS_PAID = 'paid',
    STATUS_CANCELED = 'canceled',
    STATUS_AUTHORIZED = 'authorized',
    STATUS_FAILED = 'failed',
    STATUS_ISSUED = 'issued',
    STATUS_PENDING = 'pending',
    STATUS_CLEARING = 'clearing',
    STATUS_PRELIMINARY = 'preliminary',
    STATUS_CHARGEBACK = 'chargeback';

  /**
   * Gets a status name or array of all status names keyed by it's value.
   *
   * @param string $status
   * @return array|string
   */
  public static function getStatusName($status = '') {
    $names = [
      static::STATUS_UNPAID => t('Unpaid'),
      static::STATUS_PAID => t('Paid'),
      static::STATUS_CANCELED => t('Canceled'),
      static::STATUS_AUTHORIZED => t('Authorized'),
      static::STATUS_FAILED => t('Failed'),
      static::STATUS_ISSUED => t('Issued'),
      static::STATUS_PENDING => t('Pending'),
      static::STATUS_CLEARING => t('Clearing'),
      static::STATUS_PRELIMINARY => t('Preliminary'),
      static::STATUS_CHARGEBACK => t('Chargeback'),
    ];

    return isset($names[$status]) ? $names[$status] : $names;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->get('order_reference')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrder(StoreOrderInterface $order) {
    $this->set('order_reference', $order);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUser(UserInterface $user) {
    $this->set('uid', $user->id());
    return $this;
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

  // @TODO: use localized date format

  public function getCreatedDate() {
    $date = DrupalDateTime::createFromTimestamp($this->getCreatedTime());
    return $date->format('M d, Y');
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Invoice number'))
      ->setDescription(t('The invoice number of the Invoice entity.'))
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'max_length' => 50,
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

    $fields['order_reference'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order'))
      ->setDescription(t('Reference to a order.'))
      ->setSetting('target_type', 'store_order')
      ->setRevisionable(TRUE)
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

    $fields['expiration_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Expiration date'))
      ->setDescription(t('Expiration date.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_default',
        'weight' => -10,
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

    $fields['internal_note'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Internal note'))
      ->setDescription(t('Long text field for internal use.'))
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

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Long text field with invoice details visible to user'))
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

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Invoice status'))
      ->setDescription(t('Invoice status.'))
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

    $fields['visible'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Visible'))
      ->setDescription(t('Invoice visible status.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

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
  public function getAmount() {
    if (!$this->get('amount')->isEmpty()) {
      return $this->get('amount')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount(Price $amount) {
    $this->set('amount', $amount);
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
  public function isPayable() {
    $statuses = [static::STATUS_UNPAID, static::STATUS_ISSUED, static::STATUS_PENDING, static::STATUS_FAILED];
    return $this->isVisible() && in_array($this->getStatus(), $statuses);
  }

  /**
   * {@inheritdoc}
   */
  public function isPaid() {
    return $this->getStatus() == static::STATUS_PAID ? true : false;
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    return (bool) $this->get('visible')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setVisibility($visibility) {
    $this->set('visible', $visibility);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', $description);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInvoiceNumber() {
    return $this->get('number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $this->setNewRevision(true);
  }

  /**
   * Generates unique invoice number.
   *
   * @return string
   */
  protected function generateInvoiceNumber() {
    // @todo Use configurable template to generate the invoice number.
    return time();
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomerProfile() {
    if ($this->isNew()) {
      throw new \Exception('Related customer profile can\'t be found: this invoice is not stored in database.');
    }
    $query = \Drupal::entityQuery('customer_profile');
    $result = $query->condition('invoice.target_id', $this->id())
      ->execute();
    return empty($result) ? null : \Drupal::entityTypeManager()->getStorage('customer_profile')->load(max($result));
  }

  public function getStatusMessage() {

    switch($this->getStatus()) {

      case Invoice::STATUS_FAILED:
        if ($this->isPayable()) {
          $message = t('Invoice is unpaid, you can pay it now.');
        }
        else {
          $message = t('Invoice is unpaid.');
        }
        break;

      case Invoice::STATUS_PAID:
        $message = t('This invoice has been paid. Thank you for your
      business.');
        break;

      case Invoice::STATUS_PENDING:
        $message = t('Thank you. Payment is processing.');
        break;

      case Invoice::STATUS_CLEARING:
        $message = t('Thank you. Payment is processing.');
        break;

      case Invoice::STATUS_CANCELED:
        $message = t('Invoice has been canceled.');
        break;

      default:
        $message = '';
        break;
    }

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactions() {
    if (\Drupal::moduleHandler()->moduleExists('payment')) {
      $ids = \Drupal::entityQuery('transaction')
        ->condition('invoice.target_id', $this->id())
        ->sort('id')
        ->execute();
      return \Drupal::entityTypeManager()
        ->getStorage('transaction')
        ->loadMultiple($ids);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpirationDate() {
    return $this->get('expiration_date')->date;
  }

  /**
   * {@inheritdoc}
   */
  public function setInvoiceNumber($number) {
    $this->set('number', $number);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setExpirationDate(DrupalDateTime $expiration_date) {
    $this->set('expiration_date', $expiration_date->format('Y-m-d\TH:m:i'));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestTransaction() {
    $transaction = null;
    if (\Drupal::moduleHandler()->moduleExists('payment')) {
      $ids = \Drupal::entityQuery('transaction')
        ->condition('invoice.target_id', $this->id())
        ->execute();
      if (!empty($ids)) {
        $transaction = \Drupal::entityTypeManager()->getStorage('transaction')->load(max($ids));
      }
    }

    return $transaction;
  }

}
