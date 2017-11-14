<?php

namespace Drupal\train_base;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Train brand entities.
 *
 * @ingroup train_base
 */
class TrainBrandListBuilder extends EntityListBuilder {
  
  use LinkGeneratorTrait;
  
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return array_merge(parent::buildHeader(), [
      'id' => $this->t('Train brand ID'),
      'name' => $this->t('Name'),
    ]);
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $trainBrand) {
    return array_merge(parent::buildRow($trainBrand), [
      'id' => $trainBrand->id(),
      'name' => $this->l($trainBrand->label(), new Url('entity.train_brand.edit_form', [
        'train_brand' => $trainBrand->id(),
      ])),
    ]);
  }
  
}
