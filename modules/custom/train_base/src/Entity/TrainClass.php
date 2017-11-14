<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\master\Entity\ContentEntity;

/**
 * Defines the Train class entity.
 *
 * @ingroup train_base
 *
 * @ContentEntityType(
 *   id = "train_class",
 *   label = @Translation("Train class"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\train_base\TrainClassListBuilder",
 *     "views_data" = "Drupal\train_base\Entity\TrainClassViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\train_base\Form\TrainClassForm",
 *       "add" = "Drupal\train_base\Form\TrainClassForm",
 *       "edit" = "Drupal\train_base\Form\TrainClassForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "train_class",
 *   data_table = "train_class_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer train class entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/train-class/{train_class}",
 *     "add-form" = "/admin/trains/train-class/add",
 *     "edit-form" = "/admin/trains/train-class/{train_class}/edit",
 *     "delete-form" = "/admin/trains/train-class/{train_class}/delete",
 *     "collection" = "/admin/trains/train-class",
 *   },
 *   field_ui_base_route = "entity.train_class.settings",
 *   settings_form = "Drupal\train_base\Form\TrainClassSettingsForm"
 * )
 */
class TrainClass extends ContentEntity implements TrainClassInterface {

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
  public function getDescription() {
    return $this->getTranslated('description')->value;
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
  public function isEnabled() {
    return (bool) $this->get('status')->value;
  }


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Code'))
      ->setDescription(t('The Supplier code'))
      ->setRequired(true)
      ->setSettings(array(
        'max_length' => 50,
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

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Supplier entity.'))
      ->setTranslatable(TRUE)
      ->setRequired(true)
      ->setSettings(array(
        'max_length' => 50,
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

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the Supplier entity.'))
      ->setTranslatable(TRUE)
      ->setSettings(array(
        'max_length' => 256,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'text_textarea',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['internal_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Internal description'))
      ->setDescription(t('The Internal description of the Supplier entity.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'text_textarea',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('A boolean indicating whether the Train class is on/off.'))
      ->setDefaultValue(TRUE)
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

    $fields['supplier'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Supplier'))
      ->setDescription(t('Reference to a Supplier.'))
      ->setSetting('target_type', 'supplier')
      ->setSetting('handler', 'only_enabled')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['coach_scheme'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Coach scheme'))
      ->setDescription(t('Reference to a Coach scheme.'))
      ->setSetting('target_type', 'coach_scheme')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => 1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

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
  public function setCode($code) {
    $this->set('code', $code);
    return $this;
  }

}
