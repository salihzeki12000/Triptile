<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Source plugin for the BlogTranslations.
 *
 * @MigrateSource(
 *   id = "blog_translations"
 * )
 */
class BlogTranslations extends SqlBaseTranslations {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_sc_page', 'ysp');
    $query->fields('ysp', ['title', 'content', 'meta_description', 'slug', 'lang']);
    $query->fields('ysbi', ['image', 'spoiler']);
    $query->addField('ysbi', 'id', 'blog_id');
    $query->addField('ysbc', 'slug', 'category_slug');
    $query->leftJoin('ya_sc_blog_item', 'ysbi', 'ysbi.page_id=ysp.id');
    $query->leftJoin('ya_sc_blog_item_to_category', 'ysbitc', 'ysbitc.item_id=ysbi.id');
    $query->leftJoin('ya_sc_blog_category', 'ysbc', 'ysbc.id=ysbitc.category_id');
    $query->condition('ysp.site', 'RT');
    $query->condition('ysp.lang', 'en', '!=');
    $query->condition('ysp.title', '', '!=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'title' => $this->t('Title'),
      'content' => $this->t('Body'),
      'meta_description' => $this->t('Summary'),
      'slug' => $this->t('Slug'),
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
        'alias' => 'ysp',
      ],
      'lang' => [
        'type' => 'string',
        'alias' => 'ysp',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Skip this row, if parent migration doesn't exist.
    $query = \Drupal::database()->select('migrate_map_blog', 'mmb');
    $query->addField('mmb', 'destid1', 'nid');
    $query->condition('mmb.sourceid1', $row->getSourceProperty('slug'));
    if (!$query->execute()->fetchField()) {
      return false;
    }

    // Prepare components for alias path.
    $blogCategorySlug = $row->getSourceProperty('category_slug');
    $row->setSourceProperty('bundle_path', '/blog/' . $blogCategorySlug . '/');

    // Prepare blog summary.
    if ($row->getSourceProperty('spoiler')) {
      $row->setSourceProperty('summary', $row->getSourceProperty('spoiler'));
    }
    else {
      $row->setSourceProperty('summary', $row->getSourceProperty('meta_description'));
    }

    return parent::prepareRow($row);
  }
}