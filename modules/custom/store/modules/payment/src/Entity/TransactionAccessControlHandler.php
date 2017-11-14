<?php

namespace Drupal\payment\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class TransactionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   * @param \Drupal\payment\Entity\TransactionInterface $entity
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $result = parent::checkAccess($entity, $operation, $account);

    if ($operation == 'refund') {
      if ($entity->isNew()) {
        $result = AccessResult::forbidden()->addCacheableDependency($entity);
      }
      else {
        $result = AccessResult::allowedIf($entity->isRefundable());
        $result = $result->andIf(AccessResult::allowedIfHasPermission($account, 'payment.refund_transaction'));
      }
    }

    return $result;
  }

}
