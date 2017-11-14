<?php

namespace Drupal\store\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\store\Entity\StoreOrder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StoreThankYouPage
 *
 * @package Drupal\store\Controller
 */
class StoreThankYouPage extends ControllerBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * @param \Drupal\store\Entity\StoreOrder $order_hash
   * @return array
   */
  public function userView(StoreOrder $order_hash) {
    /** @var \Drupal\store\Entity\StoreOrder $order */
    $order = $order_hash;
    $plugin_service = \Drupal::service('plugin.manager.order_renderer');
    foreach ($plugin_service->getDefinitions() as $plugin_id => $plugin) {
      if ($plugin['order_type'] == $order->getType()) {
        $instance = $plugin_service->createInstance($plugin_id);
        return $instance->getThankYouPage($order_hash);
      }
    }
    return [];
  }

  /**
   * Returns a page title.
   *
   * @param \Drupal\store\Entity\StoreOrder $order_hash
   * @return string
   */
  public function getTitle(StoreOrder $order_hash) {
    $order = $order_hash;
    $title = $this->t('Order @order_number', ['@order_number' => $order->getOrderNumber()]);
    return $title;
  }

}
