<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\store\Entity\Invoice as InvoiceEntity;

/**
 * Source plugin for the Invoice.
 *
 * @MigrateSource(
 *   id = "invoice"
 * )
 */
class Invoice extends SqlBase {

  protected static $statusesMapping = [
    'none' => InvoiceEntity::STATUS_PENDING,
    'canceled' => InvoiceEntity::STATUS_CANCELED,
    'unpaid' => InvoiceEntity::STATUS_UNPAID,
    'paid' => InvoiceEntity::STATUS_PAID,
    'pending' => InvoiceEntity::STATUS_PENDING,
    'clearing' => InvoiceEntity::STATUS_CLEARING,
    'incomplete' => InvoiceEntity::STATUS_UNPAID,
  ];

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_order_invoice', 'yoi');
//    $continueMigrationCondition = $query->orConditionGroup()
//      ->condition('yoi.created_at', '2017-07-10 04:10:20', '>')
//      ->condition('yoi.updated_at', '2017-07-10 04:10:20', '>');
    $query->fields('yoi', ['id', 'reference', 'order_id', 'user_id', 'currency',
      'amount', 'status', 'created_at', 'updated_at', 'is_visible']);
    $query->fields('sgu', ['email_address']);
    $query->join('ya_order', 'yo', 'yo.id=yoi.order_id');
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
      'id' => $this->t('Invoice id'),
      'order_id' => $this->t('StoreOrder id'),
      'reference' => $this->t('Number'),
      'user_id' => $this->t('Owner id'),
      'currency' => $this->t('Currency'),
      'amount' => $this->t('Amount'),
      'status' => $this->t('Invoice status'),
      'is_visible' => $this->t('Visible'),
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
        'alias' => 'yoi',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Prepare created and changed timestamps.
    $created = new DrupalDateTime($row->getSourceProperty('created_at'));
    $row->setSourceProperty('created', $created->getTimestamp());
    $changed = new DrupalDateTime($row->getSourceProperty('updated_at'));
    $row->setSourceProperty('changed', $changed->getTimestamp());

    // Prepare order status, use statusesMapping for setting right value.
    $orderStatus = $row->getSourceProperty('status');
    $row->setSourceProperty('status', static::$statusesMapping[$orderStatus]);

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