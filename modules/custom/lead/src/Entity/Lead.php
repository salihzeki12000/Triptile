<?php

namespace Drupal\lead\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\master\EntityWithDataPropertyInterface;
use Drupal\master\EntityWithDataPropertyTrait;
use Drupal\salesforce\Entity\MappableEntityInterface;
use Drupal\salesforce\Entity\MappableEntityTrait;

/**
 * Defines the Lead entity.
 *
 * @ingroup lead
 *
 * @ContentEntityType(
 *   id = "lead",
 *   label = @Translation("Lead"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\lead\LeadListBuilder",
 *     "views_data" = "Drupal\lead\Entity\LeadViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\lead\Form\LeadForm",
 *       "add" = "Drupal\lead\Form\LeadForm",
 *       "edit" = "Drupal\lead\Form\LeadForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\lead\LeadAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "lead",
 *   admin_permission = "administer lead entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/people/lead/{lead}",
 *     "add-form" = "/admin/people/lead/add",
 *     "edit-form" = "/admin/people/lead/{lead}/edit",
 *     "delete-form" = "/admin/people/lead/{lead}/delete",
 *     "collection" = "/admin/people/lead",
 *   },
 *   field_ui_base_route = "entity.lead.settings",
 *   settings_form = "Drupal\lead\Form\LeadSettingsForm"
 * )
 */
class Lead extends ContentEntityBase implements LeadInterface, MappableEntityInterface, EntityWithDataPropertyInterface {

  use EntityChangedTrait;
  use MappableEntityTrait;
  use EntityWithDataPropertyTrait;

  /**
   * {@inheritdoc}
   */
  public function getFirstName() {
    return $this->get('first_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirstName($firstName) {
    $this->set('first_name', $firstName);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName() {
    return $this->get('last_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastName($lastName) {
    $this->set('last_name', $lastName);
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

    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First name'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
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

    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last name'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
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

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'email_mailto',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

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

}
