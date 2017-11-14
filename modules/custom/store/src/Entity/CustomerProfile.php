<?php

namespace Drupal\store\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Customer profile entity.
 *
 * @ingroup store
 *
 * @ContentEntityType(
 *   id = "customer_profile",
 *   label = @Translation("Customer profile"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\store\CustomerProfileListBuilder",
 *     "views_data" = "Drupal\store\Entity\CustomerProfileViewsData",
 *     "form" = {
 *       "default" = "Drupal\store\Form\CustomerProfileForm",
 *       "add" = "Drupal\store\Form\CustomerProfileForm",
 *       "edit" = "Drupal\store\Form\CustomerProfileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "customer_profile",
 *   admin_permission = "administer customer profile entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/store/customer-profile/{customer_profile}",
 *     "add-form" = "/admin/store/customer-profile/add",
 *     "edit-form" = "/admin/store/customer-profile/{customer_profile}/edit",
 *     "delete-form" = "/admin/store/customer-profile/{customer_profile}/delete",
 *     "collection" = "/admin/store/customer-profile",
 *   },
 *   field_ui_base_route = "entity.customer_profile.settings",
 *   settings_form = "Drupal\store\Form\CustomerProfileSettingsForm"
 * )
 */
class CustomerProfile extends ContentEntityBase implements CustomerProfileInterface {

  use EntityChangedTrait;

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

  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (empty($this->getOwner())) {
      $this->set('uid', \Drupal::currentUser()->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user ID.'))
      ->setSetting('target_type', 'user')
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

    $fields['invoice'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Invoice'))
      ->setDescription(t('Invoice reference.'))
      ->setSetting('target_type', 'invoice')
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

    $fields['address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setDescription(t('The address of the Station.'))
      ->setSettings(array(
        'available_countries' => [],
        'fields' => [
          'administrativeArea' => 'administrativeArea',
          'locality' => 'locality',
          'dependentLocality' => 'dependentLocality',
          'postalCode' => 'postalCode',
          'sortingCode' => 'sortingCode',
          'addressLine1' => 'addressLine1',
          'addressLine2' => 'addressLine2',
          'organization' => 'organization',
          'givenName' => 'givenName',
          'additionalName' => 'additionalName',
          'familyName' => 'familyName',
        ],
        'langcode_override' => '',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'address_default',
        'weight' => -9,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'address_default',
        'weight' => -9,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['phone_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Phone number'))
      ->setDescription(t('Phone number.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The email of this customer.'))
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
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $user) {
    $this->set('uid', $user->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInvoice() {
    return $this->get('invoice')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setInvoice(InvoiceInterface $invoice) {
    $this->set('invoice', $invoice->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddress() {
    return $this->get('address')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function setAddress(array $address) {
    $this->set('address', $address);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhoneNumber() {
    return $this->get('phone_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPhoneNumber($phone_number) {
    $this->set('phone_number', $phone_number);
    return $this;
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
  public function setEmail($email) {
    $this->set('email', $email);
    return $this;
  }

}
