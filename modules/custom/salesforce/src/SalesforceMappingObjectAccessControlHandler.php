<?php

namespace Drupal\salesforce;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Salesforce mapping object entity.
 *
 * @see \Drupal\salesforce\Entity\SalesforceMappingObject.
 */
class SalesforceMappingObjectAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($admin_permission = $this->entityType->getAdminPermission()) {
      return AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission());
    }
    else {
      // No opinion.
      return AccessResult::neutral();
    }
  }

}
