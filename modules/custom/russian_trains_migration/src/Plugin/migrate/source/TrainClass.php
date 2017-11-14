<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for the TrainClass.
 *
 * @MigrateSource(
 *   id = "train_class"
 * )
 */
class TrainClass extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_train_class', 'yttc');
    $query->fields('yttc', ['id', 'code', 'enabled', 'supplier_id', 'int_descr']);
    $query->fields('yttct', ['name', 'descr', 'lang']);
    $query->join('ya_train_train_class_translation', 'yttct', 'yttc.id=yttct.id');
    $query->condition('yttct.lang', 'en');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('TrainClass id'),
      'code' => $this->t('TrainClass code'),
      'name' => $this->t('Name'),
      'lang' => $this->t('Language'),
      'enabled' => $this->t('Status'),
      'supplier_id' => $this->t('Supplier reference'),
      'int_descr' => $this->t('Internal description'),
      'descr' => $this->t('Description'),
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
        'alias' => 'yttc',
      ],
    ];
  }
}