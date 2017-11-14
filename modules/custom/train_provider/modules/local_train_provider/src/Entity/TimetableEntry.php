<?php

namespace Drupal\local_train_provider\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Timetable entry entity.
 *
 * @ingroup local_train_provider
 *
 * @ContentEntityType(
 *   id = "timetable_entry",
 *   label = @Translation("Timetable entry"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\local_train_provider\TimetableEntryListBuilder",
 *     "views_data" = "Drupal\local_train_provider\Entity\TimetableEntryViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\local_train_provider\Form\TimetableEntryForm",
 *       "add" = "Drupal\local_train_provider\Form\TimetableEntryForm",
 *       "edit" = "Drupal\local_train_provider\Form\TimetableEntryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "timetable_entry",
 *   data_table = "timetable_entry_field_data",
 *   admin_permission = "administer timetable entry entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/timetable-entry/{timetable_entry}",
 *     "add-form" = "/admin/trains/timetable-entry/add",
 *     "edit-form" = "/admin/trains/timetable-entry/{timetable_entry}/edit",
 *     "delete-form" = "/admin/trains/timetable-entry/{timetable_entry}/delete",
 *     "collection" = "/admin/trains/timetable-entry",
 *   },
 *   field_ui_base_route = "entity.timetable_entry.settings",
 *   settings_form = "Drupal\local_train_provider\Form\TimetableEntrySettingsForm"
 * )
 */
class TimetableEntry extends ContentEntityBase implements TimetableEntryInterface {

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
  public function isEnabled() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
   public function getTrain() {
     return $this->get('train')->entity;
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
  public function getChangeStation() {
    return $this->get('change_station')->entity;
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
  public function getDepartureTime() {
    return $this->get('departure_time')->departure_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getRunningTime() {
    return $this->get('running_time')->running_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getMinDepartureWindow() {
    return $this->get('min_departure_window')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMinDepartureWindow($number) {
    $this->set('min_departure_window', $number);
    return $this;
  }

  /**
   * @return float
   */
  public function getDistance(): float {
      return $this->getDepartureStation()->getDistanceTo($this->getArrivalStation());
  }

  /**
   * @return \Drupal\local_train_provider\Plugin\Field\FieldType\Schedule
   */
  private function getSchedule() {
    return $this->get('schedule')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function getEveryNDays() {
    return $this->getSchedule()->getValue()['every_n_days'] ?? null;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableFrom() {
    return $this->getSchedule()->getValue()['available_from'] ?? null;
  }

  /**
   * {@inheritdoc}
   */
  public function getLockedDay() {
    return $this->get('locked_day')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getProducts() {
    return $this->get('product')->referencedEntities();
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['train'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Train'))
      ->setDescription(t('Reference to a Train.'))
      ->setSetting('target_type', 'train')
      ->setSetting('handler', 'with_supplier')
      ->setRequired(true)
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

    $fields['departure_station'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Departure station'))
      ->setDescription(t('Reference to a Station.'))
      ->setSetting('target_type', 'station')
      ->setSetting('handler', 'only_enabled')
      ->setRequired(true)
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

    $fields['arrival_station'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Arrival station'))
      ->setDescription(t('Reference to a Station.'))
      ->setSetting('target_type', 'station')
      ->setSetting('handler', 'only_enabled')
      ->setRequired(true)
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

    $fields['departure_time'] = BaseFieldDefinition::create('departure_time')
      ->setLabel(t('Departure time'))
      ->setDescription(t('The departure time HH:mm.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'departure_time_default',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'departure_time_default',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['running_time'] = BaseFieldDefinition::create('running_time')
      ->setLabel(t('Running time'))
      ->setDescription(t('The Running time N days HH:mm and Arrival time HH:mm.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'running_time_default',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'running_time_default',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['schedule'] = BaseFieldDefinition::create('schedule')
      ->setLabel(t('Schedule'))
      ->setDescription(t('The Schedule, week days, month even/odd days, every N(2,3,4) days.'))
      ->setRequired(true)
      ->setSettings(array(
        'datetime_type' => 'date',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'schedule_default',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'schedule_default',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('A boolean indicating whether the Timetable entry is on/off.'))
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

    $fields['min_departure_window'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Minimal departure window'))
      ->setDescription(t('The minimal period starting from the current day when the train ticket can be booked; it’s the same as ‘Only after 45 days’ but more flexible.'))
      ->setDefaultValue(3)
      ->setRequired(true)
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

    $fields['locked_day'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Locked day'))
      ->setDescription(t('The days are excluded from schedule.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
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

    $fields['product'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Product'))
      ->setDescription(t('Reference to the ticket product.'))
      ->setSetting('target_type', 'base_product')
      ->setSetting('handler_settings', ['target_bundles' => ['ticket_product']])
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRequired(true)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => -2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'inline_entity_form_complex',
        'weight' => -2,
        'settings' => array(
          'allow_existing' => true,
        ),
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

    $allowedValues['disabled'] = t('Do not update');
    $trainProviderManager = \Drupal::service('plugin.manager.train_provider');
    foreach ($trainProviderManager->getDefinitions() as $pluginId => $definition) {
      if ($definition['price_updater']) {
        $allowedValues[$definition['id']] = $definition['label'];
      }
    }
    $fields['price_updater'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Price updater'))
      ->setDefaultValue('disabled')
      ->setSettings(array(
        'allowed_values' => $allowedValues,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['depth_price_update'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Depth for price update request'))
      ->setDefaultValue(30)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price_update_timestamp'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Price update was'));

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
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriceUpdater() {
    return $this->get('price_updater')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPriceUpdater($priceUpdater) {
    $this->set('price_updater', $priceUpdater);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDepthForPriceUpdate() {
    return $this->get('depth_price_update')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDepthForPriceUpdate($depth) {
    $this->set('depth_price_update', $depth);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastPriceUpdateTimestamp() {
    return $this->get('price_update_timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastPriceUpdateTimestamp($timestamp) {
    $this->set('price_update_timestamp', $timestamp);
    return $this;
  }
}
