<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\master\Entity\ContentEntity;

/**
 * Defines the Coach class entity.
 *
 * @ingroup train_base
 *
 * @ContentEntityType(
 *   id = "coach_class",
 *   label = @Translation("Coach class"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\train_base\CoachClassListBuilder",
 *     "views_data" = "Drupal\train_base\Entity\CoachClassViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\train_base\Form\CoachClassForm",
 *       "add" = "Drupal\train_base\Form\CoachClassForm",
 *       "edit" = "Drupal\train_base\Form\CoachClassForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "coach_class",
 *   data_table = "coach_class_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer coach class entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/coach-class/{coach_class}",
 *     "add-form" = "/admin/trains/coach-class/add",
 *     "edit-form" = "/admin/trains/coach-class/{coach_class}/edit",
 *     "delete-form" = "/admin/trains/coach-class/{coach_class}/delete",
 *     "collection" = "/admin/trains/coach-class",
 *   },
 *   field_ui_base_route = "entity.coach_class.settings",
 *   settings_form = "Drupal\train_base\Form\CoachClassSettingsForm"
 * )
 */
class CoachClass extends ContentEntity implements CoachClassInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->getTranslated('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCode() {
    return $this->get('code')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCode($code) {
    $this->set('code', $code);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getTranslated('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCarServices() {
    return $this->get('car_service')->referencedEntities();
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
  public function isEnabled(): bool {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Code'))
      ->setDescription(t('The Coach class code'))
      ->setRequired(true)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Coach class entity.'))
      ->setTranslatable(true)
      ->setRequired(true)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the Coach class entity.'))
      ->setTranslatable(true)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['internal_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Internal description'))
      ->setDescription(t('The Internal description of the Coach class entity.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);
    
    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Sort order.'))
      ->setRequired(true)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('A boolean indicating whether the Coach class is on/off.'))
      ->setDefaultValue(true)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    // @todo Reference to Gallery node type only.
    $fields['gallery'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Gallery'))
      ->setDescription(t('Reference to a Gallery.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['gallery']])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -2,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['car_service'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Car service'))
      ->setDescription(t('Reference to a Car service.'))
      ->setSetting('target_type', 'car_service')
      ->setSetting('handler', 'only_enabled')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['supplier'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Supplier'))
      ->setDescription(t('Reference to a Supplier.'))
      ->setSetting('target_type', 'supplier')
      ->setSetting('handler', 'only_enabled')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['train_brand'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Brand'))
      ->setDescription(t('Reference to a Train brand.'))
      ->setSetting('target_type', 'train_brand')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  public function __toString() {
    return $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getSupplier() {
    return $this->get('supplier')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setSupplier($supplier) {
    $this->set('supplier', $supplier);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrainBrands() {
    return $this->get('train_brand')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getGallery() {
    return $this->get('gallery')->entity;
  }

}
