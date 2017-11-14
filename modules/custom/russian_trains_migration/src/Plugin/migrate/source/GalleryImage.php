<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for the SupplierLogo.
 *
 * @MigrateSource(
 *   id = "gallery_image"
 * )
 */
class GalleryImage extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_gallery', 'yg');
    $query->fields('yg', ['root_path']);
    $query->fields('ygi', ['id', 'path']);
    $query->join('ya_gallery_image', 'ygi', 'yg.id=ygi.gallery_id');
    $query->condition('yg.reference_type', 'yaTrainCarClassGallery');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Galley image id'),
      'path' => $this->t('Image path'),
      'root_path' => $this->t('Root path'),
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
        'alias' => 'ygi',
      ],
    ];
  }
}