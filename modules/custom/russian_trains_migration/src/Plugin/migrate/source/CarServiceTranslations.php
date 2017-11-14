<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

/**
 * Source plugin for the CarServiceTranslations.
 *
 * @MigrateSource(
 *   id = "car_service_translations"
 * )
 */
class CarServiceTranslations extends SqlBaseTranslations {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_car_service', 'ytcs');
    $query->fields('ytcs', ['id']);
    $query->fields('ytcst', ['name', 'lang']);
    $query->join('ya_train_car_service_translation', 'ytcst', 'ytcs.id=ytcst.id');
    $query->condition('ytcst.lang', 'en', '!=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('CarService id'),
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
        'alias' => 'ytcs',
      ],
      'lang' => [
        'type' => 'string',
        'alias' => 'ytcst',
      ],
    ];
  }
}