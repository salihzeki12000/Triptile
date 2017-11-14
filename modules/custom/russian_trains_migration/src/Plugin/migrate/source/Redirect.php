<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Source plugin for the Redirect.
 *
 * @MigrateSource(
 *   id = "redirect"
 * )
 */
class Redirect extends CSV {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $externalLink = parse_url($row->getSourceProperty('external_link'))['path'];
    $redirectSource = substr($externalLink, 4);
    $row->setSourceProperty('redirect_source', $redirectSource);

    $pathAlias = parse_url($row->getSourceProperty('alias_path'))['path'];
    $language = substr($pathAlias, 1, 2);
    $redirectUri = substr($pathAlias, 3);

    $alias = \Drupal::service('path.alias_storage')->load(['alias' => $redirectUri]);
    if (!$alias) {
      \Drupal::logger('russian_trains_migration')->error($pathAlias . ' alias not found!');
      return false;
    }

    $row->setSourceProperty('language', $language);
    $row->setSourceProperty('redirect_uri', 'internal:' . $alias['source']);

    return parent::prepareRow($row);
  }
}