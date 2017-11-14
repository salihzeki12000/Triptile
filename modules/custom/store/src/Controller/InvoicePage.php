<?php

namespace Drupal\store\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\store\Entity\Invoice;

/**
 * Class InvoicePage
 *
 * @package Drupal\store\Controller
 */
class InvoicePage extends ControllerBase {

  /**
   * @param \Drupal\store\Entity\Invoice $invoice_number
   * @return array
   */
  public function userView(Invoice $invoice_number) {
    $invoice = $invoice_number;

    return [
      '#theme' => 'invoice_page',
      '#invoice' => $invoice
    ];
  }

  /**
   * Returns a page title.
   *
   * @param \Drupal\store\Entity\Invoice $invoice_number
   * @return string
   */
  public function getTitle(Invoice $invoice_number) {
    $invoice = $invoice_number;

    $title = $this->t('Invoice @invoice_number', ['@invoice_number' => $invoice->getInvoiceNumber()]);
    return $title;
  }

}
