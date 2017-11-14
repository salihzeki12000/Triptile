<?php

namespace Drupal\express3_train_provider;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Express3station entity.
 *
 * @see \Drupal\express3_train_provider\Entity\Express3Station.
 */
class Express3StationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\express3_train_provider\Entity\Express3StationInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view express3station entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit express3station entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete express3station entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add express3station entities');
  }

}
