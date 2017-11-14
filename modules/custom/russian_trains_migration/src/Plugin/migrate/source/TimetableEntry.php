<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the TimetableEntry.
 *
 * @MigrateSource(
 *   id = "timetable_entry"
 * )
 */
class TimetableEntry extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_timetable', 'yt');
    $query->fields('yt', ['id', 'code', 'departure_time', 'running_time',  'departure_station_code', 'departure_rail_station_id',
      'arrival_station_code', 'arrival_rail_station_id',  'week_days', 'dw_apply', 'month_even_days', 'month_odd_days', 'available_from', 'available_until', 'every_other_days', 'enabled']);
    $query->addField('ytsd', 'id', 'departure_station_id');
    $query->addField('ytsa', 'id', 'arrival_station_id');
    $query->leftJoin('ya_train_station', 'ytsd', 'yt.departure_station_code=ytsd.code');
    $query->leftJoin('ya_train_station', 'ytsa', 'yt.arrival_station_code=ytsa.code');
    $query->distinct(true);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Timetable entry id'),
      'code' => $this->t('Train number'),
      'departure_time' => $this->t('Departure time'),
      'running_time' => $this->t('Running time'),
      'departure_station_code' => $this->t('Departure station code'),
      'departure_rail_station_id' => $this->t('Children departure station id'),
      'arrival_station_code' => $this->t('Arrival station code'),
      'arrival_rail_station_id' => $this->t('Children arrival station id'),
      'week_days' => $this->t('Week days'),
      'dw_apply' => $this->t('Departure window'),
      'month_even_days' => $this->t('Even days'),
      'month_odd_days' => $this->t('Odd days'),
      'available_from' => $this->t('Available from date'),
      'available_until' => $this->t('Available until date'),
      'every_other_days' => $this->t('Every N days'),
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
        'alias' => 'yt',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Check train available field value.
    /** @var  $trainStorage \Drupal\Core\Entity\EntityStorageInterface */
    $trainStorage =  $query = \Drupal::service('entity_type.manager')->getStorage('train');
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $trainStorage->getQuery();
    $query->condition('number', $row->getSourceProperty('code'));
    $ids = $query->execute();
    if (!$ids) {
      return false;
    }

    $departureRailStationId = $row->getSourceProperty('departure_rail_station_id');
    if (!$departureRailStationId || $departureRailStationId == 14 || $departureRailStationId == 15) {
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

    $arrivalRailStationId = $row->getSourceProperty('arrival_rail_station_id');
    if (!$arrivalRailStationId || $arrivalRailStationId == 14 || $arrivalRailStationId == 15) {
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

    // Set minimal departure window for timetable entries, if dw_aplly was checked.
    if ($row->getSourceProperty('dw_apply')) {
      $row->setSourceProperty('dw_apply', 45);
    }

    // We use one field, for checking even/odd days.
    if ($row->getSourceProperty('month_even_days')) {
      $row->setSourceProperty('week_days', null);
      $row->setSourceProperty('even_days', 2);
    }
    if ($row->getSourceProperty('month_odd_days')) {
      $row->setSourceProperty('week_days', null);
      $row->setSourceProperty('even_days', 1);
    }

    // Setting every N days null, instead 0.
    if (!$row->getSourceProperty('every_other_days')) {
      $row->setSourceProperty('every_other_days', null);
    }

    // Prepare ticket products for this timetable entry.
    $query = $this->select('ya_train_timetable_price', 'yttp');
    $query->addField('yttp', 'id', 'product_id');
    $query->condition('yttp.timetable_id', $row->getSourceProperty('id'));
    $products = $query->execute()->fetchAll();
    if ($products) {
      $row->setSourceProperty('product', $products);
    }

    return parent::prepareRow($row);
  }
}