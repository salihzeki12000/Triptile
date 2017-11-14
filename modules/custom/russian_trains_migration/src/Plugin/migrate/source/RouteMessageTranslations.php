<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

/**
 * Source plugin for the RouteMessageTranslations.
 *
 * @MigrateSource(
 *   id = "route_message_translations"
 * )
 */
class RouteMessageTranslations extends SqlBaseTranslations {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_map_message', 'ytmm');
    $query->fields('ytmm', ['id']);
    $query->fields('ytmmt', ['note', 'lang']);
    $query->join('ya_train_map_message_translation', 'ytmmt', 'ytmm.id=ytmmt.id');
    $query->condition('ytmmt.lang', 'en', '!=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Route message ID'),
      'note' => $this->t('Note'),
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
        'alias' => 'ytmm',
      ],
      'lang' => [
        'type' => 'string',
        'alias' => 'ytmmt',
      ],
    ];
  }
}