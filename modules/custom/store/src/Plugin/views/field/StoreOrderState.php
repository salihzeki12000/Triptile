<?php

namespace Drupal\store\Plugin\views\field;

use Drupal\store\Entity\StoreOrder;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Class StoreViewLinkToInvoice
 *
 * @ViewsField("store_store_order_state")
 */
class StoreOrderState extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\store\Entity\StoreOrder $order */
    $order = $this->getEntity($values);
    return StoreOrder::getStateName($order->getState());
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing since the field is computed.
  }

}
