<?php

namespace Drupal\store\Plugin\views\field;

use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * Class StoreViewLinkToInvoice
 *
 * @ViewsField("store_view_link_to_invoice")
 */
class StoreViewLinkToInvoice extends LinkBase {

  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\store\Entity\Invoice $invoice */
    $invoice = $this->getEntity($row);
    switch ($invoice->getStatus()) {
      case 'paid':
        $link =  Url::fromRoute('entity.invoice.user_view', ['invoice_number' => $invoice->id()]);
        break;
      case 'unpaid':
        $link =  Url::fromRoute('entity.invoice.payment', ['invoice_number' => $invoice->id()]);
        break;
      default:
        $link =  Url::fromRoute('entity.invoice.user_view', ['invoice_number' => $invoice->id()]);
    }
    return $link;
  }

}
