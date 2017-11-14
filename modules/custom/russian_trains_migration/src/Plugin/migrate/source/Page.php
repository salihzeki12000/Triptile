<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the Page.
 *
 * @MigrateSource(
 *   id = "page"
 * )
 */
class Page extends SqlBase {

  static $slugMapping = [
    'train_page' => [
      'sapsan-train',
      'red-arrow',
      'allegro-train',
      'grand-express',
      'rossiya-train',
      'luxury-trains',
      'russian-rails-map',
      'nevsky-express',
      'moscow-sochi-train',
      'tolstoy-train',
      'search',
      'trans-european',
      'train-seat-types',
      'lastochka-news',
      'regular-trains',
      'aeroexpress-trains',
      'order',
      'moscow-berlin-swift-train',
      'moscow-belgorod-high-speed-train',
      'swift-train',
    ],
    'route_page'=> [
      'moscow-to-saint-petersburg',
      'trans-siberian',
      'beijing-ulan-bator-irkutsk-moscow-st-petersburg',
    ],
    'page' => [
      'train-seat-types',
      'train-cancellation-terms',
      'discount-policies',
      'advantages-of-booking-with-russiantrains',
      'size-and-weight-of-luggage',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_sc_page', 'ysp');
    $query->fields('ysp', ['title', 'content', 'meta_description', 'is_published', 'slug', 'lang']);
    $query->condition('ysp.site', 'RT');
    $query->condition('ysp.lang', 'en');
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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Prepare components for alias path.
    $row->setSourceProperty('bundle_path', '/page/');
    $slug = $row->getSourceProperty('slug');
    foreach (static::$slugMapping as $bundle => $slugs) {
      if (in_array($slug, $slugs)) {
        $row->setSourceProperty('bundle', $bundle);
        break;
      }
      else {
        return false;
      }
    }

    return parent::prepareRow($row);
  }
}