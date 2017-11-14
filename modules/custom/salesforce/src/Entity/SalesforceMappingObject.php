<?php

namespace Drupal\salesforce\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\master\EntityWithDataPropertyInterface;
use Drupal\master\EntityWithDataPropertyTrait;
use Drupal\salesforce\SalesforceSync;

/**
 * Defines the Salesforce mapping object entity.
 *
 * @ContentEntityType(
 *   id = "salesforce_mapping_object",
 *   label = @Translation("Salesforce mapping object"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\salesforce\SalesforceMappingObjectListBuilder",
 *     "views_data" = "Drupal\salesforce\Entity\SalesforceMappingObjectViewsData",
 *     "form" = {
 *       "default" = "Drupal\salesforce\Form\SalesforceMappingObjectForm",
 *       "add" = "Drupal\salesforce\Form\SalesforceMappingObjectForm",
 *       "edit" = "Drupal\salesforce\Form\SalesforceMappingObjectForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\salesforce\SalesforceMappingObjectAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\salesforce\SalesforceMappingObjectHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "salesforce_mapping_object",
 *   admin_permission = "administer salesforce mapping object entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/salesforce/salesforce-mapping-object/{salesforce_mapping_object}",
 *     "add-form" = "/admin/salesforce/salesforce-mapping-object/add",
 *     "edit-form" = "/admin/salesforce/salesforce-mapping-object/{salesforce_mapping_object}/edit",
 *     "delete-form" = "/admin/salesforce/salesforce-mapping-object/{salesforce_mapping_object}/delete",
 *     "collection" = "/admin/salesforce/salesforce-mapping-object",
 *   },
 *   field_ui_base_route = "entity.salesforce_mapping_object.settings",
 *   settings_form = "\Drupal\salesforce\Form\SalesforceMappingObjectSettingsForm"
 * )
 */
class SalesforceMappingObject extends ContentEntityBase implements SalesforceMappingObjectInterface, EntityWithDataPropertyInterface {

  use EntityChangedTrait;
  use EntityWithDataPropertyTrait;

  /**
   * A key used to store salesforce record.
   */
  const RECORD_KEY = 'record';

  /**
   * Gets options for mapping field.
   *
   * @return array
   */
  public static function mappingOptions() {
    $options = [];
    foreach (\Drupal::service('plugin.manager.salesforce_mapping')->getDefinitions() as $plugin_id => $definition) {
      $options[$plugin_id] = $definition['label'];
    }
    return $options;
  }

  /**
   * @var bool
   */
  protected $isSyncProcessing = false;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['record_id'] = BaseFieldDefinition::create('string')
      ->setLabel('Salesforce ID')
      ->setSettings(array(
        'max_length' => 18,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['salesforce_object'] = BaseFieldDefinition::create('string')
      ->setLabel('Salesforce object type')
      ->setSettings(array(
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel('Entity ID')
      ->setSettings(array(
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_type_id'] = BaseFieldDefinition::create('string')
      ->setLabel('Entity type')
      ->setSettings(array(
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_sync_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel('Last synchronization time')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'weight' => -3,
      ))
      ->setDisplayOptions('form', array(
        'weight' => -3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_action'] = BaseFieldDefinition::create('list_string')
      ->setLabel('Last synchronization action')
      ->setSetting('allowed_values', [
        SalesforceSync::SYNC_ACTION_PUSH => t('Push'),
        SalesforceSync::SYNC_ACTION_PULL => t('Pull'),
        SalesforceSync::SYNC_ACTION_DELETE => t('Delete'),
      ])
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'weight' => -2,
      ))
      ->setDisplayOptions('form', array(
        'weight' => -2,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['next_action'] = BaseFieldDefinition::create('list_string')
      ->setLabel('Scheduled synchronization action')
      ->setSetting('allowed_values', [
        SalesforceSync::SYNC_ACTION_PUSH => t('Push'),
        SalesforceSync::SYNC_ACTION_PULL => t('Pull'),
        SalesforceSync::SYNC_ACTION_DELETE => t('Delete'),
      ])
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'weight' => -2,
      ))
      ->setDisplayOptions('form', array(
        'weight' => -2,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel('Last synchronization status')
      ->setSetting('allowed_values', [
        SalesforceSync::SYNC_STATUS_SUCCESS => t('Success'),
        SalesforceSync::SYNC_STATUS_ERROR => t('Error'),
        SalesforceSync::SYNC_STATUS_CONFLICT => t('Conflict'),
      ])
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'weight' => -1,
      ))
      ->setDisplayOptions('form', array(
        'weight' => -1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tries'] = BaseFieldDefinition::create('integer')
      ->setLabel('Count of tries for last sync')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'weight' => -1,
      ))
      ->setDisplayOptions('form', array(
        'weight' => -1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mapping'] = BaseFieldDefinition::create('list_string')
      ->setLabel('Mapping plugin')
      ->setSetting('allowed_values_function', '\Drupal\salesforce\Entity\SalesforceMappingObject::mappingOptions')
      ->setRequired(true)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'weight' => -1,
      ))
      ->setDisplayOptions('form', array(
        'weight' => -1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel('Data');

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel('Name')
      ->setComputed(true)
      ->setClass('\Drupal\salesforce\ComputedField\MappingObjectNameComputed');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextSyncAction() {
    return $this->get('next_action')->value;
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
  public function resetTries() {
    return $this->setTries(0);
  }

  /**
   * {@inheritdoc}
   */
  public function setTries($count) {
    $this->set('tries', $count);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setNextSyncAction($action) {
    $this->set('next_action', $action);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecordId($record_id) {
    $record_id = \Drupal::service('salesforce_api')->convertId($record_id);
    $this->set('record_id', $record_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSalesforceObject($salesforce_object) {
    $this->set('salesforce_object', $salesforce_object);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMappedEntityId($entity_id) {
    $this->set('entity_id', $entity_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMappedEntityTypeId($entity_type_id) {
    $this->set('entity_type_id', $entity_type_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecordId() {
    return $this->get('record_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSalesforceObject() {
    return $this->get('salesforce_object')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTries() {
    return $this->get('tries')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappedEntityId() {
    return $this->get('entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappedEntityTypeId() {
    return $this->get('entity_type_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastSyncAction($action) {
    $this->set('last_action', $action);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastSyncTime($time) {
    $this->set('last_sync_time', $time);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function syncStart() {
    $this->isSyncProcessing = true;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function syncFinish() {
    $this->isSyncProcessing = false;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSyncProcessing() {
    return $this->isSyncProcessing;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSyncAction() {
    return $this->get('last_action')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSyncTime() {
    return $this->get('last_sync_time')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapping() {
    return $this->get('mapping')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMapping($mapping) {
    $this->set('mapping', $mapping);
    return $this;
  }

}
