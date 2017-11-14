<?php

namespace Drupal\train_booking;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Detailed success search entities.
 *
 * @ingroup train_booking
 */
class SuccessSearchDetailedListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Detailed success search ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\train_booking\Entity\SuccessSearchDetailed */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.success_search_detailed.edit_form', array(
          'success_search_detailed' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
