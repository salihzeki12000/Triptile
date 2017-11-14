<?php

namespace Drupal\master\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Class EntityOnlyEnabledSelection
 *
 * @EntityReferenceSelection(
 *   id = "only_enabled",
 *   label = @Translation("Only enabled"),
 *   group = "only_enabled",
 *   weight = 0
 * )
 */
class EntityOnlyEnabledSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    $target_type = $this->configuration['target_type'];

    $interfaces = class_implements(\Drupal::service('entity_type.manager')->getDefinition($target_type)->getClass());
    if (in_array('Drupal\master\Entity\EntityEnabledInterface', $interfaces)) {
      $query->condition('status', 1);
    }

    return $query;
  }

}
