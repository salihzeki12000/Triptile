<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the SeatPreference.
 *
 * @MigrateSource(
 *   id = "seat_preference"
 * )
 */
class SeatPreference extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_seat_pref', 'ytsp');
    $query->fields('ytsp', ['id', 'sort_order', 'enabled']);
    $query->fields('ytspt', ['name', 'lang']);
    $query->join('ya_train_seat_pref_translation', 'ytspt', 'ytsp.id=ytspt.id');
    $query->condition('ytspt.lang', 'en');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('SeatPreference id'),
      'enabled' => $this->t('Status'),
      'sort_order' => $this->t('Weight'),
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
        'alias' => 'ytsp',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Prepare seat types for this seat preference.
    $query = $this->select('ya_train_seat_class_seat_pref', 'ytscsp');
    $query->fields('ytscsp', ['seat_class_id']);
    $query->condition('ytscsp.seat_preference_id', $row->getSourceProperty('id'));
    $seatType = $query->execute()->fetchAll();
    if ($seatType) {
      $row->setSourceProperty('seat_type', $seatType);
    }

    // Prepare car_service for this seat preference.
    $query = $this->select('ya_train_car_service_seat_pref', 'ytcssp');
    $query->fields('ytcssp', ['car_service_id']);
    $query->condition('ytcssp.seat_preference_id', $row->getSourceProperty('id'));
    $carService = $query->execute()->fetchAll();
    if ($carService) {
      $row->setSourceProperty('car_service', $carService);
    }

    // Prepare car_service for this seat preference.
    $query = $this->select('ya_train_supplier_seat_pref', 'ytssp');
    $query->fields('ytssp', ['supplier_id']);
    $query->condition('ytssp.seat_preference_id', $row->getSourceProperty('id'));
    $supplier = $query->execute()->fetchAll();
    if ($supplier) {
      $row->setSourceProperty('supplier', $supplier);
    }

    return parent::prepareRow($row);
  }
}