<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Source plugin for the TrainTranslations.
 *
 * @MigrateSource(
 *   id = "train_translations"
 * )
 */
class TrainTranslations extends SqlBaseTranslations {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_timetable', 'yt');
    $query->fields('yt', ['id', 'code']);
    $query->fields('yttt', ['name', 'descr', 'lang']);
    $query->join('ya_train_timetable_translation', 'yttt', 'yt.id=yttt.id');
    $query->condition('yttt.lang', 'ya', '!=');
    $query->distinct(true);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Train id'),
      'code' => $this->t('Train number'),
      'name' => $this->t('Name'),
      'descr' => $this->t('Description'),
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
        'alias' => 'yt',
      ],
      'lang' => [
        'type' => 'string',
        'alias' => 'yttt',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Check train available field value.
    /** @var  $trainStorage \Drupal\Core\Entity\EntityStorageInterface */
    $trainStorage = $query = \Drupal::service('entity_type.manager')
      ->getStorage('train');
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $trainStorage->getQuery();
    $query->condition('number', $row->getSourceProperty('code'));
    $ids = $query->execute();
    if (!$ids) {
      return FALSE;
    }

    return parent::prepareRow($row);
  }
}