<?php

namespace Drupal\store\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\master\Entity\ContentEntity;
use Drupal\master\EntityWithDataPropertyInterface;
use Drupal\master\EntityWithDataPropertyTrait;

/**
 * Defines the Order item entity.
 *
 * @ingroup store
 *
 * @ContentEntityType(
 *   id = "order_item",
 *   label = @Translation("Order item"),
 *   bundle_label = @Translation("Order item type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\store\OrderItemListBuilder",
 *     "views_data" = "Drupal\store\Entity\OrderItemViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\store\Form\OrderItemForm",
 *       "add" = "Drupal\store\Form\OrderItemForm",
 *       "edit" = "Drupal\store\Form\OrderItemForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "order_item",
 *   data_table = "order_item_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer order item entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/store/order-item/{order_item}",
 *     "add-page" = "/admin/store/order-item/add",
 *     "add-form" = "/admin/store/order-item/add/{order_item_type}",
 *     "edit-form" = "/admin/store/order-item/{order_item}/edit",
 *     "delete-form" = "/admin/store/order-item/{order_item}/delete",
 *     "collection" = "/admin/store/order-item",
 *   },
 *   bundle_entity_type = "order_item_type",
 *   field_ui_base_route = "entity.order_item_type.edit_form",
 *   settings_form = "Drupal\store\Form\OrderItemSettingsForm"
 * )
 */
class OrderItem extends ContentEntity implements OrderItemInterface, EntityWithDataPropertyInterface {

  use EntityChangedTrait;
  use EntityWithDataPropertyTrait;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function setOrder($order_id) {
    $this->set('order_reference', $order_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    return $this->get('quantity')->value;
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
  public function getPrice() {
    if (!$this->get('price')->isEmpty()) {
      return $this->get('price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setPrice($price) {
    $this->set('price', $price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalPrice() {
    if (!$this->get('original_price')->isEmpty()) {
      return $this->get('original_price')->first()->toPrice();
    }
    else {
      if ($product = $this->getProduct()) {
        return $product->getPrice();
      }
    }

    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalPrice($price) {
    $this->set('original_price', $price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    if (!$this->get('price')->isEmpty()) {
      return $this->getPrice()->getCurrencyCode();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLegNumber() {
    $definitions = $this->getFieldDefinitions();
    if (isset($definitions['leg_number'])) {
      return $this->get('leg_number')->value;
    }

    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function setLegNumber($leg_number) {
    $definitions = $this->getFieldDefinitions();
    if (isset($definitions['leg_number'])) {
      $this->set('leg_number', $leg_number);
    }

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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The order item name.'))
      ->setTranslatable(TRUE)
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Quantity'))
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

    $fields['order_reference'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order reference'))
      ->setDescription(t('Reference to an order.')) 
      ->setSetting('target_type', 'store_order')
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

    $fields['original_price'] = BaseFieldDefinition::create('price')
      ->setLabel(t('Original price'))
      ->setDescription(t('The price without any markup.'))
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

    $fields['price'] = BaseFieldDefinition::create('price')
      ->setLabel(t('Price'))
      ->setDescription(t('Price.'))
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

    $fields['product'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Product reference'))
      ->setDescription(t('Product reference.')) 
      ->setSetting('target_type', 'base_product')
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

    $fields['price_components'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Price components'))
      ->setDescription(t('Serialized field.'))
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

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel('Data');

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
  public function getProduct() {
    return $this->get('product')->entity;
  }
}
