<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the Gallery.
 *
 * @MigrateSource(
 *   id = "gallery"
 * )
 */
class Gallery extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_gallery', 'yg');
    $query->fields('yg', ['id', 'name']);
    $query->condition('yg.reference_type', 'yaTrainCarClassGallery');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Gallery id'),
      'name' => $this->t('Name'),
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
        'alias' => 'yg',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Prepare gallery image for this gallery.
    $query = $this->select('ya_gallery_image', 'ygi');
    $query->addField('ygi', 'id', 'gallery_image_id');
    $query->addField('ygit', 'name', 'alt');
    $query->addField('ygit', 'descr', 'title');
    $query->join('ya_gallery_image_translation', 'ygit', 'ygi.id=ygit.id');
    $query->condition('ygi.gallery_id', $row->getSourceProperty('id'));
    $query->condition('ygit.lang', 'en');
    $galleryImage = $query->execute()->fetchAll();
    if ($galleryImage) {
      $row->setSourceProperty('gallery_image', $galleryImage);
    }

    return parent::prepareRow($row);
  }
}