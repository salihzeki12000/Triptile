<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for the BlogTaxonomy.
 *
 * @MigrateSource(
 *   id = "blog_taxonomy"
 * )
 */
class BlogTaxonomy extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_sc_blog_category', 'ysbc');
    $query->fields('ysbc', ['slug', 'title', 'meta_description', 'lang']);
    $query->condition('ysbc.site', 'RT');
    $query->condition('ysbc.lang', 'en');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'title' => $this->t('Title'),
      'meta_description' => $this->t('Description'),
      'lang' => $this->t('Language'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'slug' => [
        'type' => 'string',
        'alias' => 'ysbc',
      ],
    ];
  }
}