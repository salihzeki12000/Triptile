<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

/**
 * Source plugin for the SeatTypeTranslations.
 *
 * @MigrateSource(
 *   id = "seat_type_translations"
 * )
 */
class SeatTypeTranslations extends SqlBaseTranslations {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_seat_class', 'ytsc');
    $query->fields('ytsc', ['id']);
    $query->fields('ytsct', ['name', 'lang']);
    $query->join('ya_train_seat_class_translation', 'ytsct', 'ytsc.id=ytsct.id');
    $query->condition('ytsct.lang', 'en', '!=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('SeatType id'),
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
        'alias' => 'ytsc',
      ],
      'lang' => [
        'type' => 'string',
        'alias' => 'ytsct',
      ],
    ];
  }
}