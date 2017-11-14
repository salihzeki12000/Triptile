<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;

/**
 * Provides entity destination plugin.
 *
 * @MigrateDestination(
 *   id = "entity:node"
 * )
 */
class Page extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $ids = parent::import($row, $old_destination_id_values);
    $entity = $this->getEntity($row, $old_destination_id_values);
    if ($entity->bundle() == 'page' || $entity->bundle() == 'blog') {
      $lang = $row->getSourceProperty('lang');
      $slug = $row->getSourceProperty('slug');
      $bundlePath = $row->getSourceProperty('bundle_path');
      if ($lang == 'en') {
        $aliasPath = '/en' . $bundlePath . $slug;
      }
      else {
        $aliasPath = $bundlePath . $slug;
      }
      \Drupal::service('path.alias_storage')->save('/node/' . reset($ids), $aliasPath, $lang);
    }

    return $ids;
  }
}