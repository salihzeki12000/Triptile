<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for the SeatType.
 *
 * @MigrateSource(
 *   id = "seat_type"
 * )
 */
class SeatType extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_seat_class', 'ytsc');
    $query->fields('ytsc', ['id', 'code', 'capacity', 'enabled', 'supplier_id', 'int_descr']);
    $query->fields('ytsct', ['name', 'lang']);
    $query->join('ya_train_seat_class_translation', 'ytsct', 'ytsc.id=ytsct.id');
    $query->condition('ytsct.lang', 'en');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('SeatType id'),
      'code' => $this->t('SeatType code'),
      'capacity' => $this->t('Capacity'),
      'name' => $this->t('Name'),
      'lang' => $this->t('Language'),
      'enabled' => $this->t('Status'),
      'supplier_id' => $this->t('Supplier reference'),
      'int_descr' => $this->t('Description'),
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
        'alias' => 'ytsc',
      ],
    ];
  }
}