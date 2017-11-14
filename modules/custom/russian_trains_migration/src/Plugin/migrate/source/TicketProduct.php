<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the TicketProduct.
 *
 * @MigrateSource(
 *   id = "ticket_product"
 * )
 */
class TicketProduct extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_timetable_price', 'yttp');
    $query->fields('yttp', ['id', 'timetable_id', 'car_class_id', 'seat_class_id', 'supplier_price', 'supplier_currency', 'enabled']);
    $query->isNotNull('yttp.car_class_id');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Product id'),
      'timetable_id' => $this->t('Timetable entry id'),
      'car_class_id' => $this->t('Coach class id'),
      'seat_class_id' => $this->t('Seat type id'),
      'supplier_price' => $this->t('Amount'),
      'supplier_currency' => $this->t('Currency'),
      'enabled' => $this->t('Status'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'yttp',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('supplier_price', round($row->getSourceProperty('supplier_price')));
    return parent::prepareRow($row);
  }
}