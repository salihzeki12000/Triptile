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
 * Defines the Hotel entity.
 *
 * @ingroup trip_base
 *
 * @ContentEntityType(
 *   id = "hotel",
 *   label = @Translation("Hotel"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\trip_base\HotelListBuilder",
 *     "views_data" = "Drupal\trip_base\Entity\HotelViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "rest" = "Drupal\trip_base\Entity\HotelRestHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\trip_base\Form\HotelForm",
 *       "add" = "Drupal\trip_base\Form\HotelForm",
 *       "edit" = "Drupal\trip_base\Form\HotelForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "hotel",
 *   data_table = "hotel_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer hotel entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/hotel/{hotel}",
 *     "add-form" = "/admin/trips/hotel/add",
 *     "edit-form" = "/admin/trips/hotel/{hotel}/edit",
 *     "delete-form" = "/admin/trips/hotel/{hotel}/delete",
 *     "collection" = "/admin/trips/hotel",
 *   },
 *   field_ui_base_route = "entity.hotel.settings",
 *   settings_form = "Drupal\trip_base\Form\HotelSettingsForm"
 * )
 */
class Hotel extends ContentEntity implements HotelInterface, MappableEntityInterface {

  use EntityChangedTrait;
  use MappableEntityTrait;

  protected static $starSettings = ['2', '3', '4', '4+', '5', '5+'];

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
  public function getStar() {
    return $this->get('star')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStar($star) {
    $this->set('star', $star);
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
  public function setDescription($description) {
    $this->set('description', $description);
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
  public function getAddress() {
      return $this->getTranslated('address');
  }

  /**
   * {@inheritdoc}
   */
  public function setAddress($country, $city, $street) {
    $country_list = \Drupal::service('address.country_repository')->getList();
    $country_code = array_search($country, $country_list);
    $this->set('address', array('country_code' => $country_code, 'locality' => $city, 'address_line1' => $street));
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
  public function getPreferred() {
    return $this->get('preferred')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreferred($preferred) {
    $this->set('preferred', $preferred ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Hotel entity.'))
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
      ->setDescription(t('The description of the Hotel.'))
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

    $fields['star'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Stars'))
      ->setDescription(t('Stars of the hotel.'))
      ->setRequired(true)
      ->setSettings(['allowed_values' => array_combine(static::$starSettings, static::$starSettings)])
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -8,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
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

    $fields['preferred'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Preferred'))
      ->setDescription(t('If checked the hotel will be used as default for the Hub.'))
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

    $fields['address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setDescription(t('The address of the Hotel.'))
      ->setTranslatable(true)
      ->setRequired(true)
      ->setSettings(array(
        'available_countries' => [],
        'fields' => [
          'administrativeArea' => 0,
          'locality' => 'locality',
          'dependentLocality' => 0,
          'postalCode' => 0,
          'sortingCode' => 0,
          'addressLine1' => 'addressLine1',
          'addressLine2' => 0,
          'organization' => 0,
          'givenName' => 0,
          'additionalName' => 0,
          'familyName' => 0,
        ],
        'langcode_override' => '',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'address_default',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'address_default',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Hotel is published.'))
      ->setDefaultValue(TRUE)
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
