<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

/**
 * Source plugin for the StationTranslations.
 *
 * @MigrateSource(
 *   id = "station_translations"
 * )
 */
class StationTranslations extends SqlBaseTranslations {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_station', 'yts');
    $query->fields('yts', ['id']);
    $query->fields('ytst', ['name', 'lang']);
    $query->join('ya_train_station_translation', 'ytst', 'yts.id=ytst.id');
    $query->condition('ytst.lang', 'en', '!=');
    $query->condition('ytst.name', '', '!=');
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
}