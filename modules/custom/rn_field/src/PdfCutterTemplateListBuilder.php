<?php

namespace Drupal\rn_field;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of pdf cutter template entities.
 */
class PdfCutterTemplateListBuilder extends ConfigEntityListBuilder {
  
  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    return array_merge([
      'label' => $this->t('Name'),
    ], parent::buildHeader());
  }
  
  /**
   * {@inheritdoc}
   *
   * @param \Drupal\rn_field\Entity\PdfCutterTemplate $entity
   */
  public function buildRow(EntityInterface $entity): array {
    return array_merge([
      'label' => $entity->label(),
    ], parent::buildRow($entity));
  }
  
}
