<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

/**
 * Source plugin for the SalesforceMappingObjectInvoice.
 *
 * @MigrateSource(
 *   id = "salesforce_mapping_object_invoice"
 * )
 */
class SalesforceMappingObjectInvoice extends SalesforceMappingObject {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_sforce_binding', 'ysb');
    $query->fields('ysb', ['id', 'sforce_id', 'sforce_type', 'model_class', 'model_key', 'key_value', 'updated_at']);
    $query->addField('yoi', 'id', 'invoice_id');
    $query->join('ya_order_invoice', 'yoi', 'yoi.reference=ysb.key_value');
    $query->join('ya_order', 'yo', 'yo.id=yoi.order_id');
    $query->condition('ysb.model_class', 'yaOrderInvoice');
    $query->condition('yo.type', 1);
    $query->condition('yo.site', 'RT');
    return $query;
  }
}