<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for the CarService.
 *
 * @MigrateSource(
 *   id = "car_service"
 * )
 */
class CarService extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_car_service', 'ytcs');
    $query->fields('ytcs', ['id', 'code', 'enabled', 'supplier_id', 'int_descr']);
    $query->fields('ytcst', ['name', 'lang']);
    $query->join('ya_train_car_service_translation', 'ytcst', 'ytcs.id=ytcst.id');
    $query->condition('ytcst.lang', 'en');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('CarService id'),
      'code' => $this->t('CarService code'),
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
        'alias' => 'ytcs',
      ],
    ];
  }
}