<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the CoachClass.
 *
 * @MigrateSource(
 *   id = "coach_class"
 * )
 */
class CoachClass extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_car_class', 'ytcc');
    $galleryCondition = $query->orConditionGroup()
      ->condition('yg.reference_type', 'yaTrainCarClassGallery')
      ->isNull('yg.reference_type');
    $languageCondition = $query->orConditionGroup()
      ->condition('ytcct.lang', 'en')
      ->isNull('ytcct.lang');
    $query->fields('ytcc', ['id', 'code', 'enabled', 'supplier_id', 'int_descr', 'sort_order']);
    $query->fields('ytcct', ['name', 'descr', 'lang']);
    $query->addField('yg', 'id', 'gallery_id');
    $query->leftJoin('ya_train_car_class_translation', 'ytcct', 'ytcc.id=ytcct.id');
    $query->leftJoin('ya_gallery', 'yg', 'ytcc.id=yg.reference_id');
    $query->condition($galleryCondition);
    $query->condition($languageCondition);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('CoachClass id'),
      'code' => $this->t('CoachClass code'),
      'name' => $this->t('Name'),
      'descr' => $this->t('Description'),
      'lang' => $this->t('Language'),
      'enabled' => $this->t('Status'),
      'supplier_id' => $this->t('Supplier reference'),
      'int_descr' => $this->t('Internal description'),
      'sort_order' => $this->t('Weight'),
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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Prepare car service for this coach class.
    $query = $this->select('ya_train_car_class_service_ref', 'ytccsr');
    $query->fields('ytccsr', ['car_service_id']);
    $query->condition('ytccsr.car_class_id', $row->getSourceProperty('id'));
    $carService = $query->execute()->fetchAll();
    if ($carService) {
      $row->setSourceProperty('car_service', $carService);
    }
  }

}