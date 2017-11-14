<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

/**
 * Source plugin for the TrainClassTranslations.
 *
 * @MigrateSource(
 *   id = "train_class_translations"
 * )
 */
class TrainClassTranslations extends SqlBaseTranslations {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_train_class', 'yttc');
    $query->fields('yttc', ['id']);
    $query->fields('yttct', ['name', 'descr', 'lang']);
    $query->join('ya_train_train_class_translation', 'yttct', 'yttc.id=yttct.id');
    $query->condition('yttct.lang', 'en', '!=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('TrainClass id'),
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
        'alias' => 'yttc',
      ],
      'lang' => [
        'type' => 'string',
        'alias' => 'yttct',
      ],
    ];
  }
}