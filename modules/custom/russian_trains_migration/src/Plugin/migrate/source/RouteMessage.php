<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the RouteMessage.
 *
 * @MigrateSource(
 *   id = "route_message"
 * )
 */
class RouteMessage extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_map_message', 'ytmm');
    $query->fields('ytmm', ['id', 'route_name', 'departure_station', 'arrival_station', 'enabled']);
    $query->fields('ytmmt', ['note', 'lang']);
    $query->join('ya_train_map_message_translation', 'ytmmt', 'ytmm.id=ytmmt.id');
    $query->condition('ytmmt.lang', 'en');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Route message ID'),
      'route_name' => $this->t('Route name'),
      'departure_station' => $this->t('Departure station'),
      'arrival_station' => $this->t('Arrival station'),
      'enabled' => $this->t('Status'),
      'note' => $this->t('Note'),
      'lang' => $this->t('Lang'),
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
        'alias' => 'ytmm',
      ],
    ];
  }
}