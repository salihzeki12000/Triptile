<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Source plugin for the SalesforceMappingObjectTransaction.
 *
 * @MigrateSource(
 *   id = "salesforce_mapping_object_transaction"
 * )
 */
class SalesforceMappingObjectTransaction extends SalesforceMappingObject {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_sforce_binding', 'ysb');
    $query->fields('ysb', ['id', 'sforce_id', 'sforce_type', 'model_class', 'model_key', 'key_value', 'updated_at']);
    $query->addField('ypt', 'transaction_id', 'transaction_id');
    $query->join('ya_payment_transaction', 'ypt', 'ypt.transaction_id=ysb.key_value');
    $query->join('ya_order_invoice_transaction_ref', 'yoitr', 'yoitr.transaction_id=ypt.transaction_id');
    $query->join('ya_order_invoice', 'yoi', 'yoi.id=yoitr.invoice_id');
    $query->join('ya_order', 'yo', 'yo.id=yoi.order_id');
    $query->condition('ysb.model_class', 'yaPaymentTransaction');
    $query->condition('yo.type', 1);
    $query->condition('yo.site', 'RT');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $query = \Drupal::database()->select('migrate_map_transaction_children', 'mmtc');
    $query->addField('mmtc', 'destid1', 'transaction_id');
    $query->condition('mmtc.sourceid1', $row->getSourceProperty('key_value'));
    $transactionID = $query->execute()->fetchField();
    if ($transactionID) {
      $row->setSourceProperty('transaction_id', $transactionID);
      return parent::prepareRow($row);
    }
    $query = \Drupal::database()->select('migrate_map_first_payments_transaction', 'mmfpt');
    $query->addField('mmfpt', 'destid1', 'transaction_id');
    $query->condition('mmfpt.sourceid1', $row->getSourceProperty('key_value'));
    $transactionID = $query->execute()->fetchField();
    if ($transactionID) {
      $row->setSourceProperty('transaction_id', $transactionID);
      return parent::prepareRow($row);
    }
    $query = \Drupal::database()->select('migrate_map_first_payments_children_transaction', 'mmfpct');
    $query->addField('mmfpct', 'destid1', 'transaction_id');
    $query->condition('mmfpct.sourceid1', $row->getSourceProperty('key_value'));
    $transactionID = $query->execute()->fetchField();
    if ($transactionID) {
      $row->setSourceProperty('transaction_id', $transactionID);
      return parent::prepareRow($row);
    }
    $query = \Drupal::database()->select('migrate_map_transaction', 'mmt');
    $query->addField('mmt', 'destid1', 'transaction_id');
    $query->condition('mmt.sourceid1', $row->getSourceProperty('key_value'));
    $transactionID = $query->execute()->fetchField();
    if ($transactionID) {
      $row->setSourceProperty('transaction_id', $transactionID);
      return parent::prepareRow($row);
    }

    return parent::prepareRow($row);
  }
}