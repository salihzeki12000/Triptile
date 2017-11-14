<?php

namespace Drupal\trip_base\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\master\Entity\ContentEntity;
use Drupal\salesforce\Entity\MappableEntityInterface;
use Drupal\salesforce\Entity\MappableEntityTrait;

/**
 * Defines the Activity entity.
 *
 * @ingroup trip_base
 *
 * @ContentEntityType(
 *   id = "activity",
 *   label = @Translation("Activity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\trip_base\ActivityListBuilder",
 *     "views_data" = "Drupal\trip_base\Entity\ActivityViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\trip_base\Form\ActivityForm",
 *       "add" = "Drupal\trip_base\Form\ActivityForm",
 *       "edit" = "Drupal\trip_base\Form\ActivityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "activity",
 *   data_table = "activity_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer activity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/trips/activity/{activity}",
 *     "add-form" = "/admin/trips/activity/add",
 *     "edit-form" = "/admin/trips/activity/{activity}/edit",
 *     "delete-form" = "/admin/trips/activity/{activity}/delete",
 *     "collection" = "/admin/trips/activity",
 *   },
 *   field_ui_base_route = "entity.activity.settings",
 *   settings_form = "Drupal\trip_base\Form\ActivitySettingsForm"
 * )
 */
class Activity extends ContentEntity implements ActivityInterface, MappableEntityInterface {

  use EntityChangedTrait;
  use MappableEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
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
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHub() {
    return $this->get('hub')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHub($hub) {
    $this->set('hub', $hub);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriceOpts() {
    return $this->get('price_options');
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
  public function getPreferred() {
    return $this->get('preferred')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreferred($preferred = true) {
    $this->set('preferred', $preferred);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Activity entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setTranslatable(true)
      ->setRequired(true)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the Activity.'))
      ->setTranslatable(true)
      ->setRequired(true)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rating'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Rating'))
      ->setDescription(t('The Activity rating.'))
      ->setRequired(true)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => -8,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -8,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hub'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Hub'))
      ->setSetting('target_type', 'hub')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -7,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['preferred'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Preferred'))
      ->setDescription(t('If checked the activity will be used as default for the Hub.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price_options'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Prices'))
      ->setSetting('target_type', 'base_product')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -6,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Activity is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => -5,
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
  public function setPriceOptionsIds($ids) {
    $this->set('price_options', $ids);
    return $this;
  }

}
