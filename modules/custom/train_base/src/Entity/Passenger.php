<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\salesforce\Entity\MappableEntityInterface;
use Drupal\salesforce\Entity\MappableEntityTrait;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Passenger entity.
 *
 * @ingroup train_base
 *
 * @ContentEntityType(
 *   id = "passenger",
 *   label = @Translation("Passenger"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\train_base\PassengerListBuilder",
 *     "views_data" = "Drupal\train_base\Entity\PassengerViewsData",
 *     "form" = {
 *       "default" = "Drupal\train_base\Form\PassengerForm",
 *       "add" = "Drupal\train_base\Form\PassengerForm",
 *       "edit" = "Drupal\train_base\Form\PassengerForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "passenger",
 *   data_table = "passenger",
 *   admin_permission = "administer passenger entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "last_name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/passenger/{passenger}",
 *     "add-form" = "/admin/trains/passenger/add",
 *     "edit-form" = "/admin/trains/passenger/{passenger}/edit",
 *     "delete-form" = "/admin/trains/passenger/{passenger}/delete",
 *     "collection" = "/admin/trains/passenger",
 *   },
 *   field_ui_base_route = "entity.passenger.settings",
 *   settings_form = "Drupal\train_base\Form\PassengerSettingsForm"
 * )
 */
class Passenger extends ContentEntityBase implements PassengerInterface, MappableEntityInterface {

  use EntityChangedTrait;
  use MappableEntityTrait;

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
  public function setOwner($user) {
    $this->set('uid', $user);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName() {
    return $this->get('first_name')->value;
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
  public function getName() {
    $name = array($this->getFirstName(), $this->getLastName());
    return implode(' ', $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getGender() {
    return $this->get('gender')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdNumber() {
    return $this->get('id_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDob() {
    return $this->get('dob')->date;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Set gender if it's empty but title is exists.
    if (empty($this->getGender()) && !empty($this->getTitle())) {
      switch ($this->getTitle()) {
        case 'mr':
          $this->setGender('male');
          break;
        case 'mrs' || 'miss':
          $this->setGender('female');
          break;
        default:
          $this->setGender('male');
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First Name'))
      ->setDescription(t('The first name of the Passenger entity.'))
      ->setRequired(TRUE)
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

    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last name'))
      ->setDescription(t('The last name of the Passenger entity.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -7,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -7,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Passenger entity.'))
      ->setSettings(array(
        'allowed_values' => [
          'mr' => t('Mr.'),
          'mrs' => t('Mrs.'),
          'miss' => t('Miss.'),
        ]
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

    $fields['gender'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Gender'))
      ->setDescription(t('The gender of the Passenger entity.'))
      ->setSettings(array(
        'allowed_values' => [
          'male' => t('Male'),
          'female' => t('Female'),
        ]
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

    $fields['citizenship'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Citizenship'))
      ->setDescription(t('The citizenship of the Passenger entity.'))
      ->setSettings(array(
        'allowed_values' => \Drupal::service('country_manager')->getList()
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

    $fields['id_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID number'))
      ->setDescription(t('The ID number of the Passenger entity.'))
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

    $fields['dob'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('DOB'))
      ->setDescription(t('The DOB of the Passenger entity.'))
      ->setSettings(array(
        'datetime_type' => 'date',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -4,
        'settings' => [
          'format_type' => 'html_date',
        ]
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_default',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('Reference to a user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
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
  public function getCitizenship() {
    return $this->get('citizenship')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTicket() {
    $tickets = \Drupal::entityTypeManager()->getStorage('train_ticket')->loadByProperties(['passenger' => $this->id()]);
    return reset($tickets);
  }

  /**
   * {@inheritdoc}
   */
  public function setFirstName($first_name) {
    $this->set('first_name', $first_name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastName($last_name) {
    $this->set('last_name', $last_name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setGender($gender) {
    $this->set('gender', $gender);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setIdNumber($id_number) {
    $this->set('id_number', $id_number);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDob(DrupalDateTime $dob) {
    $this->set('dob', $dob->format(DATETIME_DATE_STORAGE_FORMAT));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCitizenship($country_code) {
    $this->set('citizenship', $country_code);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

}
