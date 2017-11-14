<?php

namespace Drupal\train_booking\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Booking stat entity.
 *
 * @ingroup train_booking
 *
 * @ContentEntityType(
 *   id = "booking_stat",
 *   label = @Translation("Booking stat"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\train_booking\BookingStatListBuilder",
 *     "views_data" = "Drupal\train_booking\Entity\BookingStatViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\train_booking\Form\BookingStatForm",
 *       "add" = "Drupal\train_booking\Form\BookingStatForm",
 *       "edit" = "Drupal\train_booking\Form\BookingStatForm",
 *       "delete" = "Drupal\train_booking\Form\BookingStatDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "booking_stat",
 *   admin_permission = "administer booking stat entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/statistic/booking-stat/{booking_stat}",
 *     "add-form" = "/admin/trains/statistic/booking-stat/add",
 *     "edit-form" = "/admin/trains/statistic/booking-stat/{booking_stat}/edit",
 *     "delete-form" = "/admin/trains/statistic/booking-stat/{booking_stat}/delete",
 *     "collection" = "/admin/trains/statistic/booking-stat",
 *   },
 *   field_ui_base_route = "entity.booking_stat.settings",
 *   settings_form = "Drupal\train_booking\Form\BookingStatSettingsForm"
 * )
 */
class BookingStat extends ContentEntityBase implements BookingStatInterface {

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

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

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

    $fields['one_way_trip_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Count of one way trip bookings'))
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

    $fields['round_trip_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Count of round trip bookings'))
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

    $fields['ticket_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Count of booked tickets'))
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
  public function getOneWayTripCount() {
    return (int) $this->get('one_way_trip_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementOneWayTripCount($i = 1) {
    $this->set('one_way_trip_count', $this->getOneWayTripCount() + $i);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getComplexTripCount() {
    return (int) $this->get('round_trip_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementComplexTripCount($i = 1) {
    $this->set('round_trip_count', $this->getComplexTripCount() + $i);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTicketCount() {
    return (int) $this->get('ticket_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementTicketCount($i = 1) {
    $this->set('ticket_count', $this->getTicketCount() + $i);
    return $this;
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
  public function decrementFailedBookingCount($i = 1) {
    $this->set('failed_booking_count', $this->getFailedBookingCount() -  $i);
    return $this;
  }

}
