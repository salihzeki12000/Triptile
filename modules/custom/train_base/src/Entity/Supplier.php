<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Supplier entity.
 *
 * @ingroup train_base
 *
 * @ContentEntityType(
 *   id = "supplier",
 *   label = @Translation("Supplier"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\train_base\SupplierListBuilder",
 *     "views_data" = "Drupal\train_base\Entity\SupplierViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\train_base\Form\SupplierForm",
 *       "add" = "Drupal\train_base\Form\SupplierForm",
 *       "edit" = "Drupal\train_base\Form\SupplierForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "supplier",
 *   data_table = "supplier_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer supplier entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/supplier/{supplier}",
 *     "add-form" = "/admin/trains/supplier/add",
 *     "edit-form" = "/admin/trains/supplier/{supplier}/edit",
 *     "delete-form" = "/admin/trains/supplier/{supplier}/delete",
 *     "collection" = "/admin/trains/supplier",
 *   },
 *   field_ui_base_route = "entity.supplier.settings",
 *   settings_form = "Drupal\train_base\Form\SupplierSettingsForm"
 * )
 */
class Supplier extends ContentEntityBase implements SupplierInterface {

  use EntityChangedTrait;

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
  public function getMinChildAge() {
    return $this->get('min_child_age')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxChildAge() {
    return $this->get('max_child_age')->value;
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
  public function getLogo() {
    return $this->get('logo')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassengerFormType() {
    return $this->get('passenger_form_type')->first()->plugin_id;
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
  public function getMaxOrderDepth() {
    return $this->get('max_order_depth')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    return $this->get('currency')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $service_currency_option = \Drupal::service('currency.form_helper')->getCurrencyOptions();

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
      ->setDescription(t('The name of the Supplier.'))
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('A boolean indicating whether the Supplier is on/off.'))
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

    $fields['logo'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Logo'))
      ->setDescription(t('The logo.'))
      ->setSettings(array(
        'file_directory' => 'train_base/supplier_logo',
        'file_extensions' => 'png gif jpg jpeg',
        'max_filesize' => '',
        'max_resolution' => '',
        'min_resolution' => '',
        'alt_field' => TRUE,
        'title_field' => FALSE,
        'alt_field_required' => TRUE,
        'title_field_required' => FALSE,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'image',
        'weight' => -3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'image_image',
        'weight' => -3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['min_child_age'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Minimal child age'))
      ->setRequired(true)
      ->setSettings([
        'min' => 0,
        'max' => 30,
      ])
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

    $fields['max_child_age'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Maximal child age'))
      ->setRequired(true)
      ->setSettings([
        'min' => 0,
        'max' => 30,
      ])
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

    $fields['create_payable_invoice'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Create a payable invoice'))
      ->setDescription(t('Should we create a payable invoice for order with this supplier?'))
      ->setDefaultValue(false)
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

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'email_mailto',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'email_default',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['running_balance_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Running balance ID'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['max_order_depth'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Maximal order depth'))
      ->setSettings(['min' => 0])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['currency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Salesforce account currency'))
      ->setSettings([
        'allowed_values' => $service_currency_option,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -4,
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

  /**
   * {@inheritdoc}
   */
  public function isChild($age) {
    if ($age >= $this->getMinChildAge() && $age <= $this->getMaxChildAge()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isInfant($age) {
    if ($age < $this->getMinChildAge()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
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
  public function setCode($supplierCode) {
    $this->set('code', $supplierCode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatePayableInvoice() {
    return $this->get('create_payable_invoice')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->get('email')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRunningBalanceId() {
    return $this->get('running_balance_id')->value;
  }

}
