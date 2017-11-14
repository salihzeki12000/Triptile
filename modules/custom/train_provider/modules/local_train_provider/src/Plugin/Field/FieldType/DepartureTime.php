<?php

namespace Drupal\local_train_provider\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'departure_time' field type.
 *
 * @FieldType(
 *   id = "departure_time",
 *   label = @Translation("Departure time"),
 *   description = @Translation("Departure time"),
 *   default_widget = "departure_time_default",
 *   default_formatter = "departure_time_default"
 * )
 */
class DepartureTime extends FieldItemBase {


  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['departure_time'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Departure time'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'departure_time' => [
          'type' => 'int',
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->get('departure_time')->getValue());
  }

}
