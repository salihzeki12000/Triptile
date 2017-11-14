<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the OrderItemTicket.
 *
 * @MigrateSource(
 *   id = "order_item_ticket"
 * )
 */
class OrderItemTicket extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_order_ticket', 'ytot');
//    $continueMigrationCondition = $query->orConditionGroup()
//      ->condition('ytot.created_at', '2017-07-10 04:10:20', '>')
//      ->condition('ytot.updated_at', '2017-07-10 04:10:20', '>');
    $query->fields('ytot', ['order_id', 'departure_station_code', 'supplier_price', 'supplier_currency', 'price', 'currency', 'customer_price', 'customer_currency']);
    $query->join('ya_order', 'yo', 'yo.id=ytot.order_id');
    $query->condition('yo.type', 1);
    $query->condition('yo.site', 'RT');
    $query->distinct(TRUE);
//    $query->condition($continueMigrationCondition);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'order_id' => $this->t('Order id'),
      'supplier_price' => $this->t('Original price'),
      'supplier_currency' => $this->t('Original currency'),
      'price' => $this->t('Price'),
      'currency' => $this->t('Currency'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'order_id' => [
        'type' => 'integer',
        'alias' => 'ytot',
      ],
      'departure_station_code' => [
        'type' => 'string',
        'alias' => 'ytot',
      ],
      'customer_price' => [
        'type' => 'integer',
        'alias' => 'ytot',
      ],
      'customer_currency' => [
        'type' => 'string',
        'alias' => 'ytot',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $orderId = $row->getSourceProperty('order_id');

    // Calculate quantity.
    $query = $this->select('ya_train_order_ticket', 'ytot');
    $query->fields('ytot', ['id', 'order_id', 'departure_station_code', 'price', 'currency']);
    $query->condition('ytot.order_id', $orderId);
    $query->condition('ytot.departure_station_code', $row->getSourceProperty('departure_station_code'));
    $query->condition('ytot.price', $row->getSourceProperty('price') - 0.001, '>');
    $query->condition('ytot.price', $row->getSourceProperty('price') + 0.001, '<');
    $query->condition('ytot.currency', $row->getSourceProperty('currency'));
    $count = $query->countQuery()->execute()->fetchField();
    $row->setSourceProperty('quantity', $count);

    // We will take as an argument of leg number the order of order ticket.
    // In other words - what was saved first are the leg 1, other are the leg 2.
    $query = $this->select('ya_train_order_ticket', 'ytot');
    $query->fields('ytot', ['departure_station_code']);
    $query->condition('ytot.order_id', $orderId);
    $query->groupBy('ytot.departure_station_code');
    $result = $query->execute()->fetchAll();
    $departureStationCode = $row->getSourceProperty('departure_station_code');
    if (isset($result[1]['departure_station_code']) && $result[1]['departure_station_code'] == $departureStationCode) {
      $row->setSourceProperty('leg', 2);
    }
    else {
      $row->setSourceProperty('leg', 1);
    }

    // price is null for old orders.
    if (!$row->getSourceProperty('price')) {
      $row->setSourceProperty('price', $row->getSourceProperty('customer_price'));
      $row->setSourceProperty('currency', $row->getSourceProperty('customer_currency'));
    }

    return parent::prepareRow($row);
  }
}