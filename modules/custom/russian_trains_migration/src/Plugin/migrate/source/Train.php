<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the Train.
 *
 * @MigrateSource(
 *   id = "train"
 * )
 */
class Train extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_timetable', 'yt');
    $languageCondition = $query->orConditionGroup()
      ->condition('yttt.lang', 'en')
      ->isNull('yttt.lang');
    $query->fields('yt', ['code', 'supplier_id', 'train_class_id', 'eticket', 'board_pass_req']);
    $query->fields('ytt', ['rating_local', 'rating_tp', 'review_count']);
    $query->fields('yttt', ['name', 'descr', 'lang']);
    $query->leftJoin('ya_train_train', 'ytt', 'yt.code=ytt.code');
    $query->leftJoin('ya_train_timetable_translation', 'yttt', 'yt.id=yttt.id');
    $query->condition($languageCondition);
    $query->distinct(true);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'code' => $this->t('TrainClass code'),
      'supplier_id' => $this->t('Supplier id'),
      'train_class_id' => $this->t('Train class id'),
      'eticket' => $this->t('E-ticket available'),
      'board_pass_req' => $this->t('Boarding pass required'),
      'rating_local' => $this->t('Local rating'),
      'rating_tp' => $this->t('Trust Pilot Rating'),
      'review_count' => $this->t('Review count'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'code' => [
        'type' => 'string',
        'alias' => 'yt',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $tpRating = $row->getSourceProperty('rating_tp') * 2;
    $internalRating = $row->getSourceProperty('rating_local') * 2;
    $row->setSourceProperty('tp_rating', $tpRating);
    $row->setSourceProperty('internal_rating', $internalRating);

    return parent::prepareRow($row);
  }
}