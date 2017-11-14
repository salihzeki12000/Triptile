<?php

namespace Drupal\trip_base\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\master\Entity\ContentEntity;
use Drupal\salesforce\Entity\MappableEntityInterface;
use Drupal\salesforce\Entity\MappableEntityTrait;

/**
 * Defines the Hub entity.
 *
 * @ingroup trip_base
 *
 * @ContentEntityType(
 *   id = "hub",
 *   label = @Translation("Hub"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\trip_base\HubListBuilder",
 *     "views_data" = "Drupal\trip_base\Entity\HubViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "rest" = "Drupal\trip_base\Entity\HubRestHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\trip_base\Form\HubForm",
 *       "add" = "Drupal\trip_base\Form\HubForm",
 *       "edit" = "Drupal\trip_base\Form\HubForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "hub",
 *   data_table = "hub_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer hub entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/hub/{hub}",
 *     "add-form" = "/admin/trips/hub/add",
 *     "edit-form" = "/admin/trips/hub/{hub}/edit",
 *     "delete-form" = "/admin/trips/hub/{hub}/delete",
 *     "collection" = "/admin/trips/hub",
 *   },
 *   field_ui_base_route = "entity.hub.settings",
 *   settings_form = "Drupal\trip_base\Form\HubSettingsForm"
 * )
 */
class Hub extends ContentEntity implements HubInterface, MappableEntityInterface {

  use EntityChangedTrait;
  use MappableEntityTrait;

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
  public function getCountry() {
    return $this->getTranslated('country')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountry($country) {
    $this->set('country', $country);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegion() {
    return $this->getTranslated('region')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegion($region) {
    $this->set('region', $region);
    return $this;
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
  public function preSave(EntityStorageInterface $storage) {
    // Set region
    //$region = \Drupal::service('country_manager')->getCountryRegion($this->getCountry());
    if ($region) {
      $this->setRegion($region);
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Hub.'))
      ->setTranslatable(true)
      ->setRequired(true)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
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
      ->setDescription(t('The description of the Hub.'))
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

    $fields['geolocation'] = BaseFieldDefinition::create('geolocation')
      ->setLabel(t('Geolocation'))
      ->setDescription(t('The coordinates of the Hub.'))
      ->setRequired(true)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'geolocation_latlng',
        'weight' => -8,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'geolocation_latlng',
        'weight' => -8,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rating'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Rating'))
      ->setDescription(t('The Hub rating.'))
      ->setRequired(true)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => -7,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -7,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['country'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Country'))
      ->setDescription(t('A country the Hub is located in.'))
      ->setRequired(true)
      ->setSettings(['allowed_values' => \Drupal::service('country_manager')->getList()])
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

    $fields['region'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Region'))
      ->setDescription(t('A region the Hub is located in.'))
      ->setRequired(true)
      //->setSettings(['allowed_values' => \Drupal::service('country_manager')->getRegions()])
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -5,
      ))
      // No widget
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Recommended number of days'))
      ->setDescription(t('Number of days recommended to spent in the Hub.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['start_point'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Start point'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publish'))
      ->setDescription(t('A boolean indicating whether the Hub is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
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
   * @return \Drupal\geolocation\Plugin\Field\FieldType\GeolocationItem
   */
  protected function getGeolocation() {
    return $this->getTranslated('geolocation')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function getLatitude() {
    return $this->getGeolocation()->lat;
  }

  /**
   * {@inheritdoc}
   */
  public function getLongitude() {
    return $this->getGeolocation()->lng;
  }

  /**
   * {@inheritdoc}
   */
  public function getRating() {
    return $this->getTranslated('rating')->value;
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
  public function getRecommendedNumberOfDays() {
    return $this->getTranslated('days')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isStartPoint() {
    return (bool) $this->getTranslated('start_point')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecommendedNumberOfDays($days) {
    $this->set('days', $days);
    return $this;
  }

}
