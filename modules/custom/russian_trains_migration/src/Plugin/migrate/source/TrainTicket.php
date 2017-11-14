<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the TrainTicket.
 *
 * @MigrateSource(
 *   id = "train_ticket"
 * )
 */
class TrainTicket extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_order_ticket', 'ytot');
//    $continueMigrationCondition = $query->orConditionGroup()
//      ->condition('ytot.created_at', '2017-07-10 04:10:20', '>')
//      ->condition('ytot.updated_at', '2017-07-10 04:10:20', '>');
    $query->fields('ytot', ['id', 'order_id', 'train_number', 'departure_datetime', 'arrival_datetime',
      'departure_station_code', 'arrival_station_code']);
    $query->addField('yts', 'id', 'supplier_id');
    $query->addField('ytcc', 'id', 'coach_class_id');
    $query->addField('yttc', 'id', 'train_class_id');
    $query->addField('ytsc', 'id', 'seat_class_id');
    $query->addField('ytsd', 'id', 'departure_station_id');
    $query->addField('ytsa', 'id', 'arrival_station_id');
    $query->join('ya_train_supplier', 'yts', 'ytot.supplier_code=yts.code');
    $query->leftJoin('ya_train_car_class', 'ytcc', 'ytot.car_class_code=ytcc.code AND yts.id=ytcc.supplier_id');
    $query->leftJoin('ya_train_train_class', 'yttc', 'ytot.train_class_code=yttc.code AND yts.id=yttc.supplier_id');
    $query->leftJoin('ya_train_seat_class', 'ytsc', 'ytot.seat_class_code=ytsc.code AND yts.id=ytsc.supplier_id');
    $query->leftJoin('ya_train_station', 'ytsd', 'ytot.departure_station_code=ytsd.code');
    $query->leftJoin('ya_train_station', 'ytsa', 'ytot.arrival_station_code=ytsa.code');
//    $query->condition($continueMigrationCondition);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Product id'),
      'train_number' => $this->t('Train number'),
      'departure_datetime' => $this->t('Departure datetime'),
      'arrival_datetime' => $this->t('Arrival datetime'),
      'departure_station_code' => $this->t('Departure station code'),
      'departure_rail_station_id' => $this->t('Children departure station id'),
      'arrival_station_code' => $this->t('Arrival station code'),
      'arrival_rail_station_id' => $this->t('Children arrival station id'),
      'supplier_id' => $this->t('Supplier id'),
      'coach_class_id' => $this->t('CoachClass id'),
      'train_class_id' => $this->t('TrainClass id'),
      'seat_class_id' => $this->t('SeatClass id'),
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
        'alias' => 'ytot',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $departureDatetime = new DrupalDateTime($row->getSourceProperty('departure_datetime'));
    $row->setSourceProperty('departure_datetime', $departureDatetime->format(DATETIME_DATETIME_STORAGE_FORMAT));
    $arrivalDatetime = new DrupalDateTime($row->getSourceProperty('arrival_datetime'));
    $row->setSourceProperty('arrival_datetime', $arrivalDatetime->format(DATETIME_DATETIME_STORAGE_FORMAT));

    if (!$row->getSourceProperty('departure_rail_station_id')) {
      $query = \Drupal::database()->select('migrate_map_station', 'mms');
      $query->addField('mms', 'destid1', 'departure_station');
      $query->condition('mms.sourceid1', $row->getSourceProperty('departure_station_id'));
      $row->setSourceProperty('departure_station', $query->execute()->fetchField());
    }
    else {
      $query = \Drupal::database()->select('migrate_map_station_children', 'mmsc');
      $query->addField('mmsc', 'destid1', 'arrival_station');
      $query->condition('mmsc.sourceid1', $row->getSourceProperty('departure_rail_station_id'));
      $row->setSourceProperty('departure_station', $query->execute()->fetchField());
    }

    if (!$row->getSourceProperty('arrival_rail_station_id')) {
      $query = \Drupal::database()->select('migrate_map_station', 'mms');
      $query->addField('mms', 'destid1', 'arrival_station');
      $query->condition('mms.sourceid1', $row->getSourceProperty('arrival_station_id'));
      $row->setSourceProperty('arrival_station', $query->execute()->fetchField());
    }
    else {
      $query = \Drupal::database()->select('migrate_map_station_children', 'mmsc');
      $query->addField('mmsc', 'destid1', 'arrival_station');
      $query->condition('mmsc.sourceid1', $row->getSourceProperty('arrival_rail_station_id'));
      $row->setSourceProperty('arrival_station', $query->execute()->fetchField());
    }

    // Prepare passenger for this train ticket.
    $query = $this->select('ya_train_order_passenger', 'ytop');
    $query->addField('ytop', 'id', 'passenger_id');
    $query->condition('ytop.ticket_id', $row->getSourceProperty('id'));
    $passenger = $query->execute()->fetchAll();
    if ($passenger) {
      $row->setSourceProperty('passenger', $passenger);
    }

    // We will take as an argument of leg number the order of order ticket.
    // In other words - what was saved first are the leg 1, other are the leg 2.
    $orderId = $row->getSourceProperty('order_id');
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

    return parent::prepareRow($row);
  }
}