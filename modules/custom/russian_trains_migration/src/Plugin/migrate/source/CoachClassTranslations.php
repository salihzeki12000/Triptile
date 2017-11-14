<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

/**
 * Source plugin for the CoachClassTranslations.
 *
 * @MigrateSource(
 *   id = "coach_class_translations"
 * )
 */
class CoachClassTranslations extends SqlBaseTranslations {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_car_class', 'ytcc');
    $query->fields('ytcc', ['id']);
    $query->fields('ytcct', ['name', 'descr', 'lang']);
    $query->join('ya_train_car_class_translation', 'ytcct', 'ytcc.id=ytcct.id');
    $query->condition('ytcct.lang', 'en', '!=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('CoachClass id'),
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
        'alias' => 'ytcc',
      ],
      'lang' => [
        'type' => 'string',
        'alias' => 'ytcct',
      ],
    ];
  }
}