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
 * Defines the Connection entity.
 *
 * @ingroup trip_base
 *
 * @ContentEntityType(
 *   id = "connection",
 *   label = @Translation("Connection"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\trip_base\ConnectionListBuilder",
 *     "views_data" = "Drupal\trip_base\Entity\ConnectionViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "rest" = "Drupal\trip_base\Entity\ConnectionRestHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\trip_base\Form\ConnectionForm",
 *       "add" = "Drupal\trip_base\Form\ConnectionForm",
 *       "edit" = "Drupal\trip_base\Form\ConnectionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "connection",
 *   data_table = "connection_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer connection entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/connection/{connection}",
 *     "add-form" = "/admin/trips/connection/add",
 *     "edit-form" = "/admin/trips/connection/{connection}/edit",
 *     "delete-form" = "/admin/trips/connection/{connection}/delete",
 *     "collection" = "/admin/trips/connection",
 *   },
 *   field_ui_base_route = "entity.connection.settings",
 *   settings_form = "Drupal\trip_base\Form\ConnectionSettingsForm"
 * )
 */
class Connection extends ContentEntity implements ConnectionInterface, MappableEntityInterface {

  use EntityChangedTrait;
  use MappableEntityTrait;

  const
    TYPE_FERRY = 'ferry',
    TYPE_AIR = 'air',
    TYPE_RAIL = 'rail',
    TYPE_CAR = 'car',
    TYPE_BUS = 'bus',
    TYPE_WALK = 'walk',
    TYPE_SUBWAY = 'subway';

  public static function getTypeOptions() {
    return [
      static::TYPE_FERRY => t('Ferry'),
      static::TYPE_AIR => t('Air'),
      static::TYPE_RAIL => t('Rail'),
      static::TYPE_CAR => t('Car'),
      static::TYPE_BUS => t('Bus'),
      static::TYPE_WALK => t('Walk'),
      static::TYPE_SUBWAY => t('Subway'),
    ];
  }

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
  public function getPreferred() {
    return $this->get('preferred')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreferred() {
    $this->set('preferred', TRUE);
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Connection.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setTranslatable(true)
      ->setRequired(true)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the Connection.'))
      ->setTranslatable(TRUE)
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

    $fields['point_1'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Point 1'))
      ->setSetting('target_type', 'hub')
      ->setSetting('handler', 'default')
      ->setRequired(true)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -8,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['point_2'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Point 2'))
      ->setSetting('target_type', 'hub')
      ->setRequired(true)
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

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Type'))
      ->setDescription(t('The Connection type.'))
      ->setRequired(true)
      ->setSettings(['allowed_values' => static::getTypeOptions()])
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

    $fields['duration'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Trip duration'))
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

    $fields['rating'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Rating'))
      ->setDescription(t('The Connection rating.'))
      ->setRequired(true)
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

    $fields['overall_rating'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Overall rating'))
      ->setDescription(t('The Connection and connected Hub overall rating.'))
      ->setRequired(true)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => -3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // TODO Delete this field
    $fields['preferred'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Preferred'))
      ->setDescription(t('If checked the connection will be used as default for the Hub.'))
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
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -2,
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
      ->setDescription(t('A boolean indicating whether the Connection is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => -1,
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
  public function getDescription() {
    return $this->getTranslated('description')->processed;
  }

  /**
   * {@inheritdoc}
   */
  public function getPointA($id = false) {
    return $id ? $this->get('point_1')->target_id : $this->get('point_1')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPointB($id = false) {
    return $id ? $this->get('point_2')->target_id : $this->get('point_2')->entity;
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
  public function getDuration() {
    return $this->get('duration')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRating() {
    return $this->get('rating')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOverallRating() {
    return $this->get('overall_rating')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriceOptions() {
    return $this->get('price_options')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setPointA(Hub $pointA) {
    $this->set('point_1', $pointA->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPointB(Hub $pointB) {
    $this->set('point_2', $pointB->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPointAId($pointAId) {
    $this->set('point_1', $pointAId);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPointBId($pointBId) {
    $this->set('point_2', $pointBId);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRating($rating) {
    $this->set('rating', $rating);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOverallRating($rating) {
    $this->set('overall_rating', $rating);
    return $this;
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
  public function setPriceOptionsIds($ids) {
    $this->set('price_options', $ids);
    return $this;
  }

}
