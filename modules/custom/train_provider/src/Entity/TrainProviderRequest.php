<?php

namespace Drupal\train_provider\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\master\Entity\CountableStatEntityTrait;

/**
 * Defines the Train Provider Searching Stat entity.
 *
 * @ingroup train_provider
 *
 * @ContentEntityType(
 *   id = "train_provider_request",
 *   label = @Translation("Train provider request"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\train_provider\TrainProviderRequestListBuilder",
 *     "views_data" = "Drupal\train_provider\Entity\TrainProviderRequestViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\train_provider\Form\TrainProviderRequestForm",
 *       "add" = "Drupal\train_provider\Form\TrainProviderRequestForm",
 *       "edit" = "Drupal\train_provider\Form\TrainProviderRequestForm",
 *       "delete" = "Drupal\train_provider\Form\TrainProviderRequestDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "train_provider_request",
 *   admin_permission = "administer train provider request entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/statistic/train-provider-request/{train_provider_request}",
 *     "add-form" = "/admin/trains/statistic/train-provider-request/add",
 *     "edit-form" = "/admin/trains/statistic/train-provider-request/{train_provider_request}/edit",
 *     "delete-form" = "/admin/trains/statistic/train-provider-request/{train_provider_request}/delete",
 *     "collection" = "/admin/trains/statistic/train-provider-request",
 *   },
 *   field_ui_base_route = "entity.train_provider_request.settings",
 *   settings_form = "Drupal\train_provider\Form\TrainProviderRequestSettingsForm"
 * )
 */
class TrainProviderRequest extends ContentEntityBase implements TrainProviderRequestInterface {

  use EntityChangedTrait;
  use CountableStatEntityTrait;

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

    $fields['date_of_search'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date of search request'))
      ->setDescription(t('The date when user submitted the search request.'))
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

    $fields['depth'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Depth'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['departure_station'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Departure station'))
      ->setSetting('target_type', 'station')
      ->setSetting('handler', 'only_enabled')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => -2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => -2,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['arrival_station'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Arrival station'))
      ->setSetting('target_type', 'station')
      ->setSetting('handler', 'only_enabled')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => -2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => -2,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['provider_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Provider ID'))
      ->setDescription(t('Train provider ID.'))
      ->setTranslatable(TRUE)
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

    $fields['count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Count of success searches'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['success_booking_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Count of success bookings'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['failed_booking_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Count of failed bookings'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 3,
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
  public function getDepartureStation() {
    return $this->get('departure_station')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getArrivalStation() {
    return $this->get('arrival_station')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuccessBookingCount() {
    return (int) $this->get('success_booking_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementSuccessBookingCount($i = 1) {
    $this->set('success_booking_count', $this->getSuccessBookingCount() + $i);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFailedBookingCount() {
    return (int) $this->get('failed_booking_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementFailedBookingCount($i = 1) {
    $this->set('failed_booking_count', $this->getFailedBookingCount() + $i);
    return $this;
  }

}
