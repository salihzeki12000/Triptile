<?php

namespace Drupal\payment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Transaction entities.
 *
 * @ingroup store
 */
class TransactionListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Transaction ID');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\payment\Entity\Transaction */
    $row['id'] = $entity->id();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->access('refund')) {
      $operations['refund'] = [
        'title' => $this->t('Refund'),
        'weight' => 20,
        'url' => Url::fromRoute('payment.refund_transaction', ['transaction' => $entity->id()]),
      ];
    }

    return $operations;
  }

}
