<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the Supplier.
 *
 * @MigrateSource(
 *   id = "supplier"
 * )
 */
class Supplier extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_supplier', 'yts');
    $query->fields('yts', ['id', 'code', 'enabled', 'logo_image']);
    $query->fields('ytst', ['name', 'lang']);
    $query->join('ya_train_supplier_translation', 'ytst', 'yts.id=ytst.id');
    $query->condition('ytst.lang', 'en');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Supplier id'),
      'name' => $this->t('Supplier name'),
      'code' => $this->t('Supplier code'),
      'enabled' => $this->t('Status'),
      'logo_image' => $this->t('Logo'),
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
    // Skip suppliers which already exist in DB.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    /*$query = \Drupal::service('entity_type.manager')->getStorage('supplier')->getQuery();
    $query->condition('code', $row->getSourceProperty('code'));
    $query->condition('code', $row->getSourceProperty('code'));
    $ids = $query->execute();
    if ($ids) {
      return false;
    }*/

    return parent::prepareRow($row);
  }
}