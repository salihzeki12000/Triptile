<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

/**
 * Source plugin for the SalesforceMappingObjectPassenger.
 *
 * @MigrateSource(
 *   id = "salesforce_mapping_object_passenger"
 * )
 */
class SalesforceMappingObjectPassenger extends SalesforceMappingObject {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_sforce_binding', 'ysb');
    $query->fields('ysb', ['id', 'sforce_id', 'sforce_type', 'model_class', 'model_key', 'key_value', 'updated_at']);
    $query->addField('ytop', 'id', 'passenger_id');
    $query->join('ya_train_order_passenger', 'ytop', 'ytop.sforce_id=ysb.key_value');
    $query->condition('ysb.model_class', 'yaTrainOrderPassenger');
    return $query;
  }
}