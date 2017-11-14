<?php

namespace Drupal\local_train_provider\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'schedule' field type.
 *
 * @FieldType(
 *   id = "schedule",
 *   label = @Translation("Schedule"),
 *   description = @Translation("Schedule"),
 *   default_widget = "schedule_default",
 *   default_formatter = "schedule_default"
 * )
 */
class Schedule extends FieldItemBase {


  /**
   * Value for the 'datetime_type' setting: store only a date.
   */
  const DATETIME_TYPE_DATE = 'date';

  /**
   * Value for the 'datetime_type' setting: store a date and time.
   */
  const DATETIME_TYPE_DATETIME = 'datetime';

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'datetime_type' => 'datetime',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['weekdays'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Week days'));

    $properties['even_days'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Even days'));

    $properties['every_n_days'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Every N days'));

    $properties['available_from'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('Date value'));

    $properties['available_from_date'] = DataDefinition::create('any')
      ->setLabel(t('Computed date'))
      ->setDescription(t('The computed DateTime object.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\datetime\DateTimeComputed')
      ->setSetting('date source', 'available_from');

    $properties['available_until'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('Date value'));

    $properties['available_until_date'] = DataDefinition::create('any')
      ->setLabel(t('Computed date'))
      ->setDescription(t('The computed DateTime object.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\datetime\DateTimeComputed')
      ->setSetting('date source', 'available_until');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'weekdays' => [
          'type' => 'int',
        ],
        'even_days' => [
          'type' => 'int',
        ],
        'every_n_days' => [
          'type' => 'int',
        ],
        'available_from' => [
          'description' => 'The date value.',
          'type' => 'varchar',
          'length' => 20,
        ],
        'available_until' => [
          'description' => 'The date value.',
          'type' => 'varchar',
          'length' => 20,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    $elements['datetime_type'] = array(
      '#type' => 'select',
      '#title' => t('Date type'),
      '#description' => t('Choose the type of date to create.'),
      '#default_value' => $this->getSetting('datetime_type'),
      '#options' => array(
        static::DATETIME_TYPE_DATETIME => t('Date and time'),
        static::DATETIME_TYPE_DATE => t('Date only'),
      ),
      '#disabled' => $has_data,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->get('weekdays')->getValue()) && empty($this->get('even_days')->getValue()) && empty($this->get('every_n_days')->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Enforce that the computed date is recalculated.
    if ($property_name == 'available_from') {
      $this->available_from_date = NULL;
    }
    if ($property_name == 'available_until') {
      $this->available_until_date = NULL;
    }
    parent::onChange($property_name, $notify);
  }
}
