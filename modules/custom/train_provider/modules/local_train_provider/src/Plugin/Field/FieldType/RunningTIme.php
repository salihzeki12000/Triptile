<?php

namespace Drupal\local_train_provider\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'running_time' field type.
 *
 * @FieldType(
 *   id = "running_time",
 *   label = @Translation("Running time"),
 *   description = @Translation("Running time"),
 *   default_widget = "running_time_default",
 *   default_formatter = "running_time_default"
 * )
 */
class RunningTime extends FieldItemBase {


  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['running_time'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Running time'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'running_time' => [
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
    return empty($this->get('running_time')->getValue());
  }

}
