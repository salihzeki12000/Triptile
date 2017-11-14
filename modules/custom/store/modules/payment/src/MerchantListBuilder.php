<?php

namespace Drupal\payment;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Merchant entities.
 */
class MerchantListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['processor'] = $this->t('Processor');
    $header['merchant'] = $this->t('Merchant');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\payment\Entity\Merchant $entity
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['processor'] = $entity->getMerchantId();
    $row['merchant'] = $entity->getCompanyId();
    $row['status'] = $entity->isEnabled() ? '+' : '-';
    return $row + parent::buildRow($entity);
  }

}
