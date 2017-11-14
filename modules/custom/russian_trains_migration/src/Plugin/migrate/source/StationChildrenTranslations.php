<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

/**
 * Source plugin for the StationChildren.
 *
 * @MigrateSource(
 *   id = "station_children_translations"
 * )
 */
class StationChildrenTranslations extends SqlBaseTranslations {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_rail_station', 'ytrs');
    $query->fields('ytrs', ['id', 'enabled', 'latitude', 'longitude']);
    $query->fields('ytrst', ['name', 'lang']);
    $query->join('ya_train_rail_station_translation', 'ytrst', 'ytrs.id=ytrst.id');
    $query->condition('ytrst.lang', 'en', '!=');
    $query->condition('ytrst.name', '', '!=');
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
        'alias' => 'ytrs',
      ],
      'lang' => [
        'type' => 'string',
        'alias' => 'ytrst',
      ],
    ];
  }

}