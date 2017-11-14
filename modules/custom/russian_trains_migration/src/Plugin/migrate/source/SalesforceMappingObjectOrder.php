<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

/**
 * Source plugin for the SalesforceMappingObjectOrder.
 *
 * @MigrateSource(
 *   id = "salesforce_mapping_object_order"
 * )
 */
class SalesforceMappingObjectOrder extends SalesforceMappingObject {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_sforce_binding', 'ysb');
    $query->fields('ysb', ['id', 'sforce_id', 'sforce_type', 'model_class', 'model_key', 'key_value', 'updated_at']);
    $query->addField('yo', 'id', 'order_id');
    $query->join('ya_order', 'yo', 'yo.hash=ysb.key_value');
    $query->condition('ysb.model_class', 'yaTrainOrder');
    $query->condition('yo.type', 1);
    $query->condition('yo.site', 'RT');
    return $query;
  }
}