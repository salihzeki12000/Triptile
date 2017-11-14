<?php

namespace Drupal\train_booking\Plugin\views\field;

use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * Class TrainBookingViewLinkToTickets
 *
 * @ViewsField("train_booking_link_to_tickets")
 */
class TrainBookingViewLinkToTickets extends LinkBase {

  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\store\Entity\StoreOrder $store_order */
    $store_order = $this->getEntity($row);
    return Url::fromRoute('train_booking.tickets.download', ['order_hash' => $store_order->getHash()]);
  }

}
