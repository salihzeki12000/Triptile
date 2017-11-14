<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

/**
 * Source plugin for the SeatPreferenceTranslations.
 *
 * @MigrateSource(
 *   id = "seat_preference_translations"
 * )
 */
class SeatPreferenceTranslations extends SqlBaseTranslations {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_seat_pref', 'ytsp');
    $query->fields('ytsp', ['id']);
    $query->fields('ytspt', ['name', 'lang']);
    $query->join('ya_train_seat_class_translation', 'ytspt', 'ytsp.id=ytspt.id');
    $query->condition('ytspt.lang', 'en', '!=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('SeatPreference id'),
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
        'alias' => 'ytsp',
      ],
      'lang' => [
        'type' => 'string',
        'alias' => 'ytspt',
      ],
    ];
  }
}