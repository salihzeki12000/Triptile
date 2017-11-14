<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Source plugin for the SupplierTranslations.
 *
 * @MigrateSource(
 *   id = "supplier_translations"
 * )
 */
class SupplierTranslations extends SqlBaseTranslations {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_supplier', 'yts');
    $query->fields('yts', ['id']);
    $query->fields('ytst', ['name', 'lang']);
    $query->join('ya_train_supplier_translation', 'ytst', 'yts.id=ytst.id');
    $query->condition('ytst.lang', 'en', '!=');
    $query->condition('ytst.name', '', '!=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Supplier id'),
      'name' => $this->t('Name'),
      'lang' => $this->t('Language'),
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
      'lang' => [
        'type' => 'string',
        'alias' => 'ytst',
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