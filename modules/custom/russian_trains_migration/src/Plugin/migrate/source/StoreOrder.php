<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\store\Entity\StoreOrder as StoreOrderEntity;

/**
 * Source plugin for the StoreOrder.
 *
 * @MigrateSource(
 *   id = "store_order"
 * )
 */
class StoreOrder extends SqlBase {

  protected static $statusesMapping = [
    'none' => StoreOrderEntity::STATUS_NEW,
    'pending' => StoreOrderEntity::STATUS_PROCESSING,
    'completed' => StoreOrderEntity::STATUS_BOOKED,
    'delayed' => StoreOrderEntity::STATUS_PROCESSING,
    'canceled' => StoreOrderEntity::STATUS_CANCELED,
    'failed' => StoreOrderEntity::STATUS_FAILED,
    'modification_request' => StoreOrderEntity::STATUS_MODIFICATION_REQUESTED,
    'modified' => StoreOrderEntity::STATUS_MODIFIED,
    'verification' => StoreOrderEntity::STATUS_PROCESSING,
    'refund_request' => StoreOrderEntity::STATUS_REFUND_REQUESTED,
  ];

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_order', 'yo');
//    $continueMigrationCondition = $query->orConditionGroup()
//      ->condition('yo.created_at', '2017-07-10 04:10:20', '>')
//      ->condition('yo.updated_at', '2017-07-10 04:10:20', '>');
    $query->fields('yo', ['id', 'reference', 'user_id', 'currency', 'amount',
      'customer_amount', 'customer_currency', 'order_status', 'created_at', 'updated_at', 'lang']);
    $query->fields('sgu', ['email_address']);
    $query->join('sf_guard_user', 'sgu', 'sgu.id=yo.user_id');
    $query->condition('yo.type', 1);
    $query->condition('yo.site', 'RT');
//    $query->condition($continueMigrationCondition);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('StoreOrder id'),
      'reference' => $this->t('Number'),
      'user_id' => $this->t('Owner id'),
      'currency' => $this->t('Currency'),
      'amount' => $this->t('Amount'),
      'order_status' => $this->t('Order status'),
      'created_at' => $this->t('Created'),
      'updated_at' => $this->t('Changed'),
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
        'alias' => 'yo',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Skip saving pdf on the order without a reference.
    if (!$row->getSourceProperty('reference')) {
      $row->setSourceProperty('reference', 'not set');
    }

    // Prepare train ticket for this store order.
    $query = $this->select('ya_train_order_ticket', 'ytot');
    $query->addField('ytot', 'id', 'train_ticket_id');
    $query->condition('ytot.order_id', $row->getSourceProperty('id'));
    $trainTicket = $query->execute()->fetchAll();
    if ($trainTicket) {
      $row->setSourceProperty('train_ticket', $trainTicket);
    }

    // Prepare created and changed timestamps.
    $created = new DrupalDateTime($row->getSourceProperty('created_at'));
    $row->setSourceProperty('created', $created->getTimestamp());
    $changed = new DrupalDateTime($row->getSourceProperty('updated_at'));
    $row->setSourceProperty('changed', $changed->getTimestamp());

    // Orders from russian_trains didn't import order2 to sf, so we should avoid importing too.
    $row->setSourceProperty('data', serialize(['Order2 exported' => true, ]));

    // If order has 2 departure stations, so it's roundtrip.
    $orderId = $row->getSourceProperty('id');
    $query = $this->select('ya_train_order_ticket', 'ytot');
    $query->fields('ytot', ['departure_station_code']);
    $query->condition('ytot.order_id', $orderId);
    $query->groupBy('ytot.departure_station_code');
    $result = $query->execute()->fetchAll();
    if (count($result) == 2) {
      $row->setSourceProperty('trip_type', 'roundtrip');
    }
    else {
      $row->setSourceProperty('trip_type', 'simple');
    }

    // Prepare order status, use statusesMapping for setting right value.
    $orderStatus = $row->getSourceProperty('order_status');
    $row->setSourceProperty('status', static::$statusesMapping[$orderStatus]);

    // Amount is null for old orders.
    if (!$row->getSourceProperty('amount')) {
      $row->setSourceProperty('amount', $row->getSourceProperty('customer_amount'));
      $row->setSourceProperty('currency', $row->getSourceProperty('customer_currency'));
    }

    // Prepare lang.
    if ($row->getSourceProperty('lang') == 'cn') {
      $row->setSourceProperty('lang', 'zh-hans');
    }
    else if ($row->getSourceProperty('lang') == 'jp') {
      $row->setSourceProperty('lang', 'ja');
    }

    /** @var \Drupal\user\Entity\User $user */
    if ($user = user_load_by_mail($row->getSourceProperty('email_address'))) {
      $row->setSourceProperty('destination_user_id', $user->id());
    }
    else {
      $query = \Drupal::database()->select('migrate_map_user', 'mmu');
      $query->addField('mmu', 'destid1', 'user_id');
      $query->condition('mmu.sourceid1', $row->getSourceProperty('user_id'));
      $row->setSourceProperty('destination_user_id', $query->execute()->fetchField());
    }

    return parent::prepareRow($row);
  }
}