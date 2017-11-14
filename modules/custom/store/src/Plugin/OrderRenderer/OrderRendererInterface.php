<?php

namespace Drupal\store\Plugin\OrderRenderer;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\store\Entity\StoreOrder;

interface OrderRendererInterface extends PluginInspectionInterface {

  /**
   * Gets thank you page renderable array
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @return array
   */
  public function getThankYouPage(StoreOrder $order);

  /**
   * Gets order page renderable array
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @return array
   */
  public function getOrderPage(StoreOrder $order);

}
