<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for the BlogImage.
 *
 * @MigrateSource(
 *   id = "blog_image"
 * )
 */
class BlogImage extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_sc_blog_item', 'ysbi');
    $query->fields('ysbi', ['id', 'image']);
    $query->leftJoin('ya_sc_page', 'ysp', 'ysbi.page_id=ysp.id');
    $query->condition('ysp.site', 'RT');
    $query->condition('ysp.lang', 'en');
    $query->isNotNull('ysbi.image');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Blog image id'),
      'image' => $this->t('Image name'),
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
        'alias' => 'ysbi',
      ],
    ];
  }
}