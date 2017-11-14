<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Source plugin for the PageTranslations.
 *
 * @MigrateSource(
 *   id = "page_translations"
 * )
 */
class PageTranslations extends SqlBaseTranslations {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_sc_page', 'ysp');
    $query->fields('ysp', ['title', 'content', 'meta_description', 'is_published', 'slug', 'lang']);
    $query->condition('ysp.site', 'RT');
    $query->condition('ysp.lang', 'en', '!=');
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
      'is_published' => $this->t('Is published'),
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
    // Prepare components for alias path.
    $row->setSourceProperty('bundle_path', '/page/');

    // Skip this row, if parent migration doesn't exist.
    $query = \Drupal::database()->select('migrate_map_page', 'mmp');
    $query->addField('mmp', 'destid1', 'nid');
    $query->condition('mmp.sourceid1', $row->getSourceProperty('slug'));
    if (!$query->execute()->fetchField()) {
      return false;
    }

    return parent::prepareRow($row);
  }
}