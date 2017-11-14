<?php

namespace Drupal\train_base\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Plugin implementation of the 'supplier_mapping' field type.
 *
 * @FieldType(
 *   id = "supplier_mapping",
 *   label = @Translation("Supplier mapping"),
 *   description = @Translation("Supplier mapping"),
 *   category = @Translation("Reference"),
 *   default_widget = "supplier_mapping",
 *   default_formatter = "supplier_mapping",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList"
 * )
 */
class SupplierMapping extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['code'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Code'));
    $properties['description'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Description'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['code'] = array(
      'type' => 'varchar',
      'length' => 255,
    );
    $schema['columns']['description'] = array(
      'type' => 'text',
      'size' => 'big',
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  /*public function isEmpty() {
    //return empty($this->get('code')->getValue()) && empty($this->get('name')->getValue());
  }*/

}
