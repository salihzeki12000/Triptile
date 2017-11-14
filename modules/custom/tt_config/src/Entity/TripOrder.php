<?php
/**
 * @file
 * Contains \Drupal\tt_config\Entity\TripOrder.
 */

namespace Drupal\tt_config\Entity;

use Drupal\master\Entity\ContentEntity;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the Trip Order entity.
 *
 * @ingroup trip_order
 *
 * @ContentEntityType(
 *   id = "trip_order",
 *   label = @Translation("trip_order"),
 *   base_table = "trip_order",
 *   data_table = "trip_order_field_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "order_object" = "order_object",
   },
 * )
 */

class TripOrder extends ContentEntity implements ContentEntityInterface {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Trip Order entity.'))
      ->setReadOnly(TRUE);

    $fields['order_object'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Order object'))
      ->setDescription(t('The json of order object.'))
      ->setSettings(array(
        'text_processing' => 0,
      ));

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Order hash'))
      ->setDescription(t('Order hash.'))
      ->setSettings(array(
        'max_length' => 20,
        'text_processing' => 0,
      ));

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Trip Order entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderObject() {
    return $this->get('order_object')->value;
  }

}