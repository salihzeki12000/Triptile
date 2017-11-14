<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the SupplierLogo.
 *
 * @MigrateSource(
 *   id = "supplier_logo"
 * )
 */
class SupplierLogo extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_supplier', 'yts')
      ->fields('yts', ['logo_image', 'code'])
      ->isNotNull('logo_image');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'logo_image' => $this->t('Logo'),
      'code' => $this->t('Supplier code'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'logo_image' => [
        'type' => 'string',
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
    $ids = $query->execute();
    if ($ids) {
      return false;
    }*/

    return parent::prepareRow($row);
  }
}