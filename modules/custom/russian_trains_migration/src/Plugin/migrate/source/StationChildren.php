<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the StationChildren.
 *
 * @MigrateSource(
 *   id = "station_children"
 * )
 */
class StationChildren extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_rail_station', 'ytrs');
    $query->fields('ytrs', ['id', 'enabled', 'latitude', 'longitude', 'station_id']);
    $query->fields('yts', ['country', 'timezone']);
    $query->fields('ytrst', ['name', 'lang']);
    $query->fields('ytrse', ['code']);
    $query->leftJoin('ya_train_station', 'yts', 'ytrs.station_id=yts.id');
    $query->leftJoin('ya_train_station_translation', 'ytst', 'ytrs.station_id=ytst.id');
    $query->leftJoin('ya_train_rail_station_translation', 'ytrst', 'ytrs.id=ytrst.id');
    $query->leftJoin('ya_train_rail_station_e3', 'ytrse', 'ytrs.id=ytrse.id');
    $query->condition('ytst.lang', 'en');
    $query->condition('ytrst.lang', 'en');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Station id'),
      'name' => $this->t('Name'),
      'lang' => $this->t('Language'),
      'latitude' => $this->t('Latitude'),
      'longitude' => $this->t('Longitude'),
      'country' => $this->t('Country code'),
      'enabled' => $this->t('Status'),
      'timezone' => $this->t('Timezone'),
      'code' => $this->t('Supplier station code'),
      'station_id' => $this->t('ID of the parent station'),
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
        'alias' => 'ytrs',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($row->getSourceProperty('code')) {
      /** @var  $supplierStorage \Drupal\Core\Entity\EntityStorageInterface */
      $supplierStorage =  $query = \Drupal::service('entity_type.manager')->getStorage('supplier');
      /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
      $query = $supplierStorage->getQuery();
      $query->condition('code', 'E3');
      $ids = $query->execute();
      if ($ids) {
        $row->setSourceProperty('supplier_id', reset($ids));
      }
    }
    return parent::prepareRow($row);
  }
}