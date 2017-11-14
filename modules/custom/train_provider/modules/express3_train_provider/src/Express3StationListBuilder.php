<?php

namespace Drupal\express3_train_provider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Express3station entities.
 *
 * @ingroup express3_train_provider
 */
class Express3StationListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['code'] = $this->t('Code');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\express3_train_provider\Entity\Express3Station */
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.express3_station.edit_form', [
          'express3_station' => $entity->id(),
        ]
      )
    );
    $row['code'] = $entity->getCode();
    return $row + parent::buildRow($entity);
  }

}
