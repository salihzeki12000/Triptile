<?php

namespace Drupal\store\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\master\EntityWithDataPropertyInterface;
use Drupal\master\Master;
use Drupal\salesforce\Entity\MappableEntityInterface;
use Drupal\salesforce\Entity\MappableEntityTrait;
use Drupal\master\EntityWithDataPropertyTrait;
use Drupal\store\Price;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Defines the Store order entity.
 *
 * @ingroup store
 *
 * @ContentEntityType(
 *   id = "store_order",
 *   label = @Translation("Store order"),
 *   bundle_label = @Translation("Store order type"),
 *   handlers = {
 *     "view_builder" = "Drupal\store\StoreOrderViewBuilder",
 *     "list_builder" = "Drupal\store\StoreOrderListBuilder",
 *     "views_data" = "Drupal\store\Entity\StoreOrderViewsData",
 *     "form" = {
 *       "default" = "Drupal\store\Form\StoreOrderForm",
 *       "add" = "Drupal\store\Form\StoreOrderForm",
 *       "edit" = "Drupal\store\Form\StoreOrderForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "store_order",
 *   data_table = "store_order",
 *   revision_table = "store_order_revision",
 *   revision_data_table = "store_order_field_revision",
 *   admin_permission = "administer store order entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "number",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/store/store-order/{store_order}",
 *     "add-page" = "/admin/store/store-order/add",
 *     "add-form" = "/admin/store/store-order/add/{store_order_type}",
 *     "edit-form" = "/admin/store/store-order/{store_order}/edit",
 *     "delete-form" = "/admin/store/store-order/{store_order}/delete",
 *     "collection" = "/admin/store/store-order",
 *   },
 *   bundle_entity_type = "store_order_type",
 *   field_ui_base_route = "entity.store_order_type.edit_form",
 *   settings_form = "Drupal\store\Form\StoreOrderSettingsForm"
 * )
 */
class StoreOrder extends ContentEntityBase implements StoreOrderInterface, MappableEntityInterface, EntityWithDataPropertyInterface {

  use EntityChangedTrait;
  use MappableEntityTrait;
  use EntityWithDataPropertyTrait;

  /**
   * Order statuses.
   */
  const
    STATUS_NEW = 'new',
    STATUS_PROCESSING = 'processing',
    STATUS_FRAUD_ALERT = 'fraud_alert',
    STATUS_FRAUD = 'fraud',
    STATUS_MODIFICATION_REQUESTED = 'modification_requested',
    STATUS_REFUND_REQUESTED = 'cancellation_requested',
    STATUS_VERIFICATION = 'verification',
    STATUS_MODIFYING = 'modifying',
    STATUS_BOOKED = 'booked',
    STATUS_CLARIFICATIONS = 'clarifications',
    STATUS_MODIFIED = 'modified',
    STATUS_CANCELED = 'canceled',
    STATUS_FAILED = 'failed',
    STATUS_SOLD_OUT = 'sold_out';

  /**
   * Order states.
   */
  const
    STATE_NEW = 1,
    STATE_PROCESSING = 2,
    STATE_BOOKED = 3,
    STATE_CANCELED = 4,
    STATE_REFUND_REQUESTED = 5,
    STATE_MODIFICATION_REQUESTED = 6,
    STATE_SOLD_OUT = 7,
    STATE_FAILED = 8;

  /**
   * Mapping of order statuses to states.
   *
   * @var array
   */
  protected static $stateStatusMapping = [
    self::STATE_NEW => [self::STATUS_NEW],
    self::STATE_PROCESSING => [self::STATUS_PROCESSING, self::STATUS_FRAUD_ALERT, self::STATUS_CLARIFICATIONS, self::STATUS_VERIFICATION],
    self::STATE_BOOKED => [self::STATUS_BOOKED, self::STATUS_MODIFIED],
    self::STATE_CANCELED => [self::STATUS_CANCELED, self::STATUS_FRAUD],
    self::STATE_REFUND_REQUESTED => [self::STATUS_REFUND_REQUESTED],
    self::STATE_MODIFICATION_REQUESTED => [self::STATUS_MODIFICATION_REQUESTED, self::STATUS_MODIFYING],
    self::STATE_SOLD_OUT => [self::STATUS_SOLD_OUT],
    self::STATE_FAILED => [self::STATUS_FAILED],
  ];

  /**
   * Gets a status name or array of all status names keyed by it's value.
   *
   * @param string $status
   * @return array|string
   */
  public static function getStatusName($status = '') {
    $names = [
      self::STATUS_NEW => t('New'),
      self::STATUS_PROCESSING => t('Processing'),
      self::STATUS_FRAUD_ALERT => t('Fraud alert'),
      self::STATUS_FRAUD => t('Fraud'),
      self::STATUS_MODIFICATION_REQUESTED => t('Modification requested'),
      self::STATUS_REFUND_REQUESTED => t('Refund requested'),
      self::STATUS_VERIFICATION => t('Verification'),
      self::STATUS_MODIFYING => t('Modifying'),
      self::STATUS_BOOKED => t('Booked'),
      self::STATUS_CLARIFICATIONS => t('Clarifications'),
      self::STATUS_MODIFIED => t('Modified'),
      self::STATUS_CANCELED => t('Canceled'),
      self::STATUS_FAILED => t('Failed'),
      self::STATUS_SOLD_OUT => t('Sold out'),
    ];

    return isset($names[$status]) ? $names[$status] : $names;
  }

  public static function getStateName($state = 0) {
    $names = [
      self::STATE_NEW => t('New'),
      self::STATE_PROCESSING => t('Processing'),
      self::STATE_BOOKED => t('Booked'),
      self::STATE_CANCELED => t('Canceled'),
      self::STATE_REFUND_REQUESTED => t('Refund requested'),
      self::STATE_MODIFICATION_REQUESTED => t('Modification requested'),
      self::STATE_SOLD_OUT => t('Sold out'),
      self::STATE_FAILED => t('Failed'),
    ];

    return $names[$state] ?? $names;
  }

  /**
   * Gets a status description or array of all status descriptions keyed by
   * it's value.
   *
   * @return array|string
   */
  public function getStateDescription() {
    $descriptions = [
      self::STATE_NEW => t('This is a new order. Thank you for using our service.'),
      self::STATE_BOOKED => t('Your order is booked. Please download your tickets in PDF format from this page.'),
      self::STATE_CANCELED => t('Your order has been canceled. Thank you for using our service.'),
      self::STATE_REFUND_REQUESTED => t( 'Your order is being canceled. Once cancellation is complete, you will be notified by email.'),
      self::STATE_MODIFICATION_REQUESTED => t('Your order is being modified. Once modification is complete, you will be notified by email.'),
      self::STATE_SOLD_OUT => '',
      self::STATE_FAILED => t('Your order payment has been failed. You can try to pay again.'),
    ];

    if (!$this->getTicketIssueDate()) {
      $descriptions[static::STATE_PROCESSING] = t('We received your order. Your tickets will be issued within 1 business day and sent to your email.');
    } else {
      $descriptions[static::STATE_PROCESSING] = t('We received your order. Your tickets will be issued about @ticket_issue_date and sent to your email.', [
        '@ticket_issue_date' => $this->getTicketIssueDate()->format(DATETIME_DATE_STORAGE_FORMAT),
      ]);
    }

    return $descriptions[$this->getState()] ?? $descriptions;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
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
  public function setOwner(UserInterface $owner) {
    $this->set('owner', $owner->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('owner', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('owner')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderNumber() {
    return $this->get('number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderNumber($number) {
    $this->set('number', $number);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSiteCode($code) {
    $this->set('site', $code);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteCode() {
    return $this->get('site')->value;
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
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    foreach (static::$stateStatusMapping as $state => $statuses) {
      if (in_array($this->getStatus(), $statuses)) {
        return $state;
      }
    }

    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function getHash() {
    return $this->get('hash')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHash($hash) {
    $this->set('hash', $hash);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTripType() {
    return $this->get('trip_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTripType($trip_type) {
    $this->set('trip_type', $trip_type);
    return $this;
  }

  /**
   * @todo find a better way to set multiple field values
   *
   * {@inheritdoc}
   */
  public function setTickets($train_tickets) {
    foreach ($train_tickets as $train_ticket) {
      $this->ticket[] = $train_ticket;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    if (!$this->getSiteCode()) {
      $this->setSiteCode(Master::siteCode());
    }

    if (!$this->getStatus()) {
      $this->setStatus(static::STATUS_NEW);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $this->setNewRevision(true);

    // Set hash
    if (!$this->getHash()) {
      $this->setHash(md5($this->getOrderNumber() . time()));
    }

    // Set predefined name of PDF file.
    // @todo Find better place for file rename.
    if (!empty($this->getPdfFiles())) {
      $fileName = $this->getPdfFileName();
      foreach ($this->getPdfFiles() as $delta => $file) {
        if ($file->getFilename() != $fileName) {
          $file->setFilename($fileName);
          $newDestination = \Drupal::service('file_system')->dirname($file->getFileUri()) . '/' . $fileName;
          if ($newFile = file_move($file, $newDestination)) {
            $this->pdf_file[$delta] = $newFile->id();
          }
        }
      }
    }
  }

  /**
   * Generates PDF file name for current order.
   *
   * @return string
   */
  protected function getPdfFileName() {
    $name = '';
    if ($this->bundle() == 'train_order') {
      $depStation = $arrStation = null;
      foreach ($this->getTickets() as $ticket) {
        if (is_null($depStation) || ($depStation->id() != $ticket->getDepartureStation()->id() && $arrStation->id() != $ticket->getArrivalStation()->id())) {
          if (!is_null($depStation) && $depStation->id() != $ticket->getDepartureStation()->id() && $arrStation->id() != $ticket->getArrivalStation()->id()) {
            $name .= '--';
          }
          $depStation = $ticket->getDepartureStation();
          $arrStation = $ticket->getArrivalStation();
          $depCity = $depStation->getParentStation() ? : $depStation;
          $arrCity = $arrStation->getParentStation() ? : $arrStation;
          $name .= $depCity . '-' . $arrCity . '-' . $ticket->getDepartureDateTime()->format('Y-m-d');
        }
      }
    }

    return $name . '.pdf';
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Order number'))
      ->setDescription(t('The order number of the Store order entity.'))
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

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription(t('Unique order hash'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 40,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'type' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['owner'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user ID of owner.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['order_total'] = BaseFieldDefinition::create('price')
      ->setLabel(t('Order total'))
      ->setDescription(t('The order total.'))
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

    $fields['site'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Site'))
      ->setDescription(t('Site.'))
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'allowed_values' => [
          'RN' => 'RN',
          'RT' => 'RT',
          'RTT' => 'RTT'
        ]
      ))
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

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel('Data');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Generates order number for the order.
   *
   * @return string
   */
  protected function generateOrderNumber() {
    // @todo Use configurable template.
    return time();
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderTotal() {
    return $this->get('order_total')->first()->toPrice();
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderTotal(Price $total) {
    $this->set('order_total', $total);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTickets() {
    $tickets = [];
    if ($this->bundle() == 'train_order') {
      // @TODO: find a way to work with special fields. Search same using in the project.
      $tickets = $this->get('ticket')->referencedEntities();
    }
    return $tickets;
  }

  /**
   * {@inheritdoc}
   */
  public function getInvoices() {
    $ids = \Drupal::entityQuery('invoice')
      ->condition('order_reference.target_id', $this->id())
      ->sort('id')
      ->execute();
    return \Drupal::entityTypeManager()
      ->getStorage('invoice')
      ->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getPdfFiles() {
    $files = [];
    if ($this->bundle() == 'train_order') {
      $files = $this->get('pdf_file')->referencedEntities();
    }
    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotes() {
    $notes = '';
    if ($this->bundle() == 'train_order') {
      $notes = $this->get('user_note')->value;
    }
    return $notes;
  }

  /**
   * {@inheritdoc}
   */
  public function setNotes($notes) {
    if ($this->bundle() == 'train_order') {
      $this->set('user_note', $notes);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTicketIssueDate() {
    if (!empty($this->get('ticket_issue_date')->first())) {
      return $this->get('ticket_issue_date')->first()->date;
    }

    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function setTicketIssueDate(DrupalDateTime $ticket_issue_date) {
    $this->set('ticket_issue_date', $ticket_issue_date->format(DATETIME_DATE_STORAGE_FORMAT));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItems() {
    return \Drupal::entityTypeManager()
      ->getStorage('order_item')
      ->loadByProperties(['order_reference' => $this->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function setTrainProviders($trainProviders) {
    if ($this->bundle() == 'train_order' && !empty($trainProviders)) {
      $this->set('train_provider', $trainProviders);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrainProviders() {
    $trainProviders = [];

    if ($this->bundle() == 'train_order') {
      $trainProviders = $this->get('train_provider')->getValue();
    }

    return $trainProviders;
  }

}
