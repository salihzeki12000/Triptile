<?php

namespace Drupal\train_base\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\master\Plugin\EntityReferenceSelection\EntityOnlyEnabledSelection;
use Drupal\train_base\Entity\ReferencingToSupplier;

/**
 * Class EntityWithSupplierSelection
 *
 * @EntityReferenceSelection(
 *   id = "with_supplier",
 *   label = @Translation("With supplier"),
 *   group = "with_supplier",
 *   weight = 0
 * )
 */
class EntityWithSupplierSelection extends EntityOnlyEnabledSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->configuration['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return array();
    }

    $options = array();
    $entities = $this->entityManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $label = $this->entityManager->getTranslationFromContext($entity)->label();
      if ($entity instanceof ReferencingToSupplier && $entity->getSupplier()) {
        $label .= ' [' . $entity->getSupplier()->getCode() . ']';
      }
      $options[$bundle][$entity_id] = Html::escape($label);
    }

    return $options;
  }

}
