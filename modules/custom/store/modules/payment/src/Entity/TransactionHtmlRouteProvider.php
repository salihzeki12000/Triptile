<?php

namespace Drupal\payment\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\master\Entity\EntityHtmlRouteProvider;
use Symfony\Component\Routing\Route;

class TransactionHtmlRouteProvider extends EntityHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $collection->add('payment.refund_transaction', $this->getRefundRoute($entity_type));

    return $collection;
  }

  /**
   * Gets refund page router definition.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @return \Symfony\Component\Routing\Route
   */
  protected function getRefundRoute(EntityTypeInterface $entity_type) {
    $route = new Route($entity_type->getLinkTemplate('canonical') . '/' . $entity_type->id() . '/refund');
    $route->setDefaults([
        '_form' => 'Drupal\payment\Form\Admin\TransactionRefundForm',
        '_title' => 'Refund ' . $entity_type->getLabel(),
      ])
      ->setRequirement('_permission', 'payment.refund_transaction')
      ->setRequirement('_entity_access', $entity_type->id() . '.refund')
      ->setOption('_admin_route', TRUE);

    return $route;
  }

}
