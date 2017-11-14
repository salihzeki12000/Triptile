<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Train ticket entity.
 *
 * @ingroup train_base
 *
 * @ContentEntityType(
 *   id = "train_ticket",
 *   label = @Translation("Train ticket"),
 *   handlers = {
 *     "view_builder" = "Drupal\train_base\TrainTicketViewBuilder",
 *     "list_builder" = "Drupal\train_base\TrainTicketListBuilder",
 *     "views_data" = "Drupal\train_base\Entity\TrainTicketViewsData",
 *     "form" = {
 *       "default" = "Drupal\train_base\Form\TrainTicketForm",
 *       "add" = "Drupal\train_base\Form\TrainTicketForm",
 *       "edit" = "Drupal\train_base\Form\TrainTicketForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "train_ticket",
 *   data_table = "train_ticket",
 *   admin_permission = "administer train ticket entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/train-ticket/{train_ticket}",
 *     "add-form" = "/admin/trains/train-ticket/add",
 *     "edit-form" = "/admin/trains/train-ticket/{train_ticket}/edit",
 *     "delete-form" = "/admin/trains/train-ticket/{train_ticket}/delete",
 *     "collection" = "/admin/trains/train-ticket",
 *   },
 *   field_ui_base_route = "entity.train_ticket.settings",
 *   settings_form = "Drupal\train_base\Form\TrainTicketSettingsForm"
 * )
 */
class TrainTicket extends ContentEntityBase implements TrainTicketInterface {

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
  public function getDepartureStation() {
    return $this->get('departure_station')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setDepartureStation(Station $departure_station) {
    $this->set('departure_station', $departure_station);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangeStation() {
    return $this->get('change_station')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangeStation(StationInterface $change_station) {
    $this->set('change_station', $change_station);
    return $this;
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
  public function setArrivalStation(Station $arrival_station) {
    $this->set('arrival_station', $arrival_station);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDepartureCity() {
    return $this->getDepartureStation()->getParentStation() ?
      $this->getDepartureStation()->getParentStation(): $this->getDepartureStation();
  }

  /**
   * {@inheritdoc}
   */
  public function getArrivalCity() {
    return $this->getArrivalStation()->getParentStation() ?
      $this->getArrivalStation()->getParentStation(): $this->getArrivalStation();
  }

  /**
   * {@inheritdoc}
   */
  public function getDepartureDateTime() {
    $depDateTime = $this->get('departure_datetime')->first()->date;
    if ($depDateTime) {
      $depDateTime->setTimezone($this->getDepartureStation()->getTimezone());
    }

    return $depDateTime;
  }

  /**
   * {@inheritdoc}
   */
  public function setDepartureDateTime(DrupalDateTime $departure_datetime) {
    $this->set('departure_datetime', $departure_datetime->format(DATETIME_DATETIME_STORAGE_FORMAT, ['timezone' => \Drupal::config('system.date')->get('timezone.default')]));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getArrivalDateTime() {
    $arrDateTime = $this->get('arrival_datetime')->first()->date;
    if ($arrDateTime) {
      $arrDateTime->setTimezone($this->getArrivalStation()->getTimezone());
    }

    return $arrDateTime;
  }

  /**
   * {@inheritdoc}
   */
  public function setArrivalDateTime(DrupalDateTime $arrival_datetime) {
    $this->set('arrival_datetime', $arrival_datetime->format(DATETIME_DATETIME_STORAGE_FORMAT, ['timezone' => \Drupal::config('system.date')->get('timezone.default')]));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isBoardingPassRequired() {
    return $this->get('boarding_pass_required')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBoardingPassRequired(bool $boarding_pass_required) {
    $this->set('boarding_pass_required', $boarding_pass_required);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLegNumber() {
    return $this->get('leg_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLegNumber($leg_number) {
    $this->set('leg_number', $leg_number);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrainClass() {
    return $this->get('train_class')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setTrainClass(TrainClass $train_class) {
    $this->set('train_class', $train_class);
    return $this;
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
  public function getCoachNumber() {
    return $this->get('coach_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCoachNumber(int $coach_number) {
    $this->set('coach_number', $coach_number);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeatNumber() {
    return $this->get('seat_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSeatNumber(int $seat_number) {
    $this->set('seat_number', $seat_number);
    return $this;
  }

  /**
   * @todo find a better way to set multiple field values
   *
   * {@inheritdoc}
   */
  public function setPassengers($pids) {
    foreach ($pids as $pid) {
      $this->passenger[] = $pid;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassengers() {
    return $this->get('passenger')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getTrainNumber() {
    return $this->get('train_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTrainNumber(string $train_number) {
    $this->set('train_number', $train_number);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrainName() {
    return $this->get('train_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTrainName(string $train_name) {
    $this->set('train_name', $train_name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCarServices() {
    return $this->get('car_service')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setCarServices(array $carServices) {
    if ($carServices) {
      $this->car_service = $carServices;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['departure_station'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Departure station'))
      ->setDescription(t('Reference to a Station.'))
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
      ->setDescription(t('Reference to a Station.'))
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

    $fields['change_station'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Change station'))
      ->setDescription(t('Reference to a Station.'))
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

    $fields['departure_datetime'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Departure date and time'))
      ->setDescription(t('Departure date and time.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_default',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['arrival_datetime'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Arrival date and time'))
      ->setDescription(t('Arrival date and time.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_default',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['leg_number'] = BaseFieldDefinition::create('integer')
      ->setLabel('Leg number')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'weight' => -1,
      ))
      ->setDisplayOptions('form', array(
        'weight' => -1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['boarding_pass_required'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Boarding pass required'))
      ->setDescription(t('Boarding password required.'))
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

    $fields['train_class'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Train class'))
      ->setDescription(t('Reference to a Train class.'))
      ->setSetting('target_type', 'train_class')
      ->setSetting('handler', 'with_supplier')
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

    $fields['coach_class'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Coach class'))
      ->setDescription(t('Reference to a Coach class.'))
      ->setSetting('target_type', 'coach_class')
      ->setSetting('handler', 'with_supplier')
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

    $fields['seat_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Seat type'))
      ->setDescription(t('Reference to a Seat type.'))
      ->setSetting('target_type', 'seat_type')
      ->setSetting('handler', 'with_supplier')
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

    $fields['coach_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Coach number'))
      ->setDescription(t('Coach number.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['seat_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Seat number'))
      ->setDescription(t('Seat number.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['train_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Train number'))
      ->setDescription(t('Train number.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['train_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Train name'))
      ->setDescription(t('Train name.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['passenger'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Passenger'))
      ->setDescription(t('Reference to a Passenger.'))
      ->setSetting('target_type', 'passenger')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
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

    $fields['car_service'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Car service'))
      ->setDescription(t('Reference to a Car service.'))
      ->setSetting('target_type', 'car_service')
      ->setSetting('handler', 'only_enabled')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
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
  public function getOrder() {
    $order = null;
    if (\Drupal::moduleHandler()->moduleExists('store')) {
      $orders = \Drupal::entityTypeManager()->getStorage('store_order')->loadByProperties(['ticket' => $this->id()]);
      $order = reset($orders);
    }

    return $order;
  }

}
