<?php

namespace Drupal\store\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\master\Entity\ContentEntity;
use Drupal\salesforce\Entity\MappableEntityInterface;
use Drupal\salesforce\Entity\MappableEntityTrait;
use Drupal\store\Price;
use Drupal\train_base\Entity\CoachClass;
use Drupal\train_base\Entity\SeatType;

/**
 * Defines the Base product entity.
 *
 * @ingroup store
 *
 * @ContentEntityType(
 *   id = "base_product",
 *   label = @Translation("Base product"),
 *   bundle_label = @Translation("Base product type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\store\BaseProductListBuilder",
 *     "views_data" = "Drupal\store\Entity\BaseProductViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "rest" = "Drupal\store\Entity\BaseProductRestHandler",
 *     "form" = {
 *       "default" = "Drupal\store\Form\BaseProductForm",
 *       "add" = "Drupal\store\Form\BaseProductForm",
 *       "edit" = "Drupal\store\Form\BaseProductForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "base_product",
 *   data_table = "base_product_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer base product entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/store/base-product/{base_product}",
 *     "add-page" = "/admin/store/base-product/add",
 *     "add-form" = "/admin/store/base-product/add/{base_product_type}",
 *     "edit-form" = "/admin/store/base-product/{base_product}/edit",
 *     "delete-form" = "/admin/store/base-product/{base_product}/delete",
 *     "collection" = "/admin/store/base-product",
 *   },
 *   bundle_entity_type = "base_product_type",
 *   field_ui_base_route = "entity.base_product_type.edit_form",
 *   settings_form = "Drupal\store\Form\BaseProductSettingsForm"
 * )
 */
class BaseProduct extends ContentEntity implements BaseProductInterface, MappableEntityInterface {

  use EntityChangedTrait;
  use MappableEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
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
  public function getFieldForm() {
    return $this->get('form')->plugin_id;
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
  public function getPrice() {
    if (!$this->get('price')->isEmpty()) {
      return $this->get('price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setPrice(Price $price) {
    $this->set('price', $price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxQuantity() {
    return $this->get('max_quantity');
  }

  /**
   * {@inheritdoc}
   */
  public function setMaxQuantity($max_quantity) {
    $this->set('max_quantity', $max_quantity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    if (!$this->get('price')->isEmpty()) {
      return $this->get('price')->first()->toPrice()->getCurrencyCode();
    }
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
  public function getPriceTitle() {
    return $this->bundle() == 'optional_service' ? $this->getTranslated('price_title')->value : null;
  }

  /**
   * {@inheritdoc}
   */
  public function getCoachClass() {
    return $this->get('coach_class')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setCoachClass(CoachClass $coach_class) {
    $this->set('coach_class', $coach_class);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeatType() {
    return $this->get('seat_type')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setSeatType(SeatType $seat_type) {
    $this->set('seat_type', $seat_type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
      
    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product code'))
      ->setDescription(t('The base product code'))
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
      ->setDescription(t('The name of the base product entity.'))
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

    $fields['site'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Site'))
      ->setDescription(t('Site.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRequired(true)
      ->setSettings(array(
        'allowed_values' => [
          'RN' => 'RN',
          'RT' => 'RT',
          'TT' => 'TT',
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

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the base product entity.'))
      ->setTranslatable(TRUE)
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

    $fields['price'] = BaseFieldDefinition::create('price')
      ->setLabel(t('Price'))
      ->setDescription(t('The price of base product entity.'))
      ->setRequired(true)
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

    $fields['max_quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Max quantity'))
      ->setDescription(t('How many items can be booked per order.'))
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('A boolean indicating whether the Base product is published.'))
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
    return $this->getTranslated('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMinimalDepartureWindow() {
    $definitions = $this->getFieldDefinitions();
    if (isset($definitions['min_departure_window'])) {
      return $this->get('min_departure_window')->value;
    }

    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    if (isset($this->fieldDefinitions['default'])) {
      return (bool) $this->get('default')->value;
    }

    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableFrom() {
    if ($this->hasField('available_from_date')) {
      if ($item = $this->get('available_from_date')->first()) {
        return $item->date;
      }
    }

    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableUntil() {
    if ($this->hasField('available_until_date')) {
      if ($item = $this->get('available_until_date')->first()) {
        return $item->date;
      }
    }

    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function setAvailableFrom(DrupalDateTime $date) {
    if ($this->hasField('available_from_date')) {
      $this->set('available_from_date', $date->format(DATETIME_DATETIME_STORAGE_FORMAT, ['timezone' => \Drupal::config('system.date')->get('timezone.default')]));
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAvailableUntil(DrupalDateTime $date) {
    if ($this->hasField('available_until_date')) {
      $this->set('available_until_date', $date->format(DATETIME_DATETIME_STORAGE_FORMAT, ['timezone' => \Drupal::config('system.date')->get('timezone.default')]));
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

}
