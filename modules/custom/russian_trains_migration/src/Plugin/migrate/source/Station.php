<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the Station.
 *
 * @MigrateSource(
 *   id = "station"
 * )
 */
class Station extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_station', 'yts');
    $languageCondition = $query->orConditionGroup()
      ->condition('ytst.lang', 'en')
      ->isNull('ytst.lang');
    $query->fields('yts', ['id', 'enabled', 'country', 'latitude', 'longitude', 'timezone']);
    $query->fields('ytst', ['name', 'lang']);
    $query->fields('ytse', ['code']);
    $query->leftJoin('ya_train_station_translation', 'ytst', 'yts.id=ytst.id');
    $query->leftJoin('ya_train_station_e3', 'ytse', 'yts.id=ytse.id');
    $query->condition($languageCondition);
    $query->isNull('yts.parent_id');
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
      'code' => $this->t('Supplier station code')
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
        'alias' => 'yts',
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