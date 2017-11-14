<?php

namespace Drupal\store\Plugin\Field\FieldType;

use \Drupal\store\Price;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'price' field type.
 *
 * @FieldType(
 *   id = "price",
 *   label = @Translation("Price"),
 *   description = @Translation("Stores a decimal number and a three letter currency code."),
 *   category = @Translation("Store"),
 *   default_widget = "price_default",
 *   default_formatter = "price_default",
 * )
 */
class PriceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['number'] = DataDefinition::create('string')
      ->setLabel(t('Number'))
      ->setRequired(FALSE);

    $properties['currency_code'] = DataDefinition::create('string')
      ->setLabel(t('Currency code'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'number' => [
          'description' => 'The number.',
          'type' => 'numeric',
          'precision' => 19,
          'scale' => 6,
        ],
        'currency_code' => [
          'description' => 'The currency code.',
          'type' => 'varchar',
          'length' => 3,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->number === NULL || $this->number === '' || empty($this->currency_code);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Allow callers to pass a Price value object as the field item value.
    if ($values instanceof Price) {
      $price = $values;
      $values = [
        'number' => $price->getNumber(),
        'currency_code' => $price->getCurrencyCode(),
      ];
    }
    parent::setValue($values, $notify);
  }

  /**
   * Gets the Price value object for the current field item.
   *
   * @return \Drupal\store\Price
   *   The Price value object.
   */
  public function toPrice() {
    $price  = \Drupal::service('store.price')->get($this->number, $this->currency_code);
    return $price;
  }

}
