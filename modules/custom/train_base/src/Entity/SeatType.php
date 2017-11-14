<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\master\Entity\ContentEntity;

/**
 * Defines the Seat type entity.
 *
 * @ingroup train_base
 *
 * @ContentEntityType(
 *   id = "seat_type",
 *   label = @Translation("Seat type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\train_base\SeatTypeListBuilder",
 *     "views_data" = "Drupal\train_base\Entity\SeatTypeViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\train_base\Form\SeatTypeForm",
 *       "add" = "Drupal\train_base\Form\SeatTypeForm",
 *       "edit" = "Drupal\train_base\Form\SeatTypeForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "seat_type",
 *   data_table = "seat_type_field_data",
 *   translatable = TRUE,
  *   admin_permission = "administer seat type entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/seat-type/{seat_type}",
 *     "add-form" = "/admin/trains/seat-type/add",
 *     "edit-form" = "/admin/trains/seat-type/{seat_type}/edit",
 *     "delete-form" = "/admin/trains/seat-type/{seat_type}/delete",
 *     "collection" = "/admin/trains/seat-type",
 *   },
 *   field_ui_base_route = "entity.seat_type.settings",
 *   settings_form = "Drupal\train_base\Form\SeatTypeSettingsForm"
 * )
 */
class SeatType extends ContentEntity implements SeatTypeInterface {

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
      ->setDescription(t('The Seat type code'))
      ->setRequired(true)
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -9,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -9,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Seat type entity.'))
      ->setTranslatable(TRUE)
      ->setRequired(true)
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -8,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -8,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the Seat type entity.'))
      ->setTranslatable(TRUE)
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -7,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'text_textarea',
        'weight' => -7,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['capacity'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Capacity'))
      ->setDescription(t('Capacity.'))
      ->setRequired(true)
      ->setDefaultValue(1)
      ->setSettings(array(
        'allowed_values' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Sort order.'))
      ->setRequired(true)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('A boolean indicating whether the Seat type is on/off.'))
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
        'weight' => -2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -2,
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

  /**
   * {@inheritdoc}
   */
  public function getSupplier() {
    return $this->get('supplier')->entity;
  }

  public function __toString() {
    return $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacity() {
    return $this->get('capacity')->value;
  }

}
