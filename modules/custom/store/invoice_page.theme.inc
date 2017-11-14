<?php

/**
 * @file
 * Contains invoice.page.inc.
 *
 * Page callback for Invoice entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;



/**
 * Implements template_preprocess_hook.
 */
function template_preprocess_invoice_page(&$vars) {
  /** @var \Drupal\store\Entity\Invoice $invoice */
  $invoice = $vars['invoice'];

  $invoice_page = array();
  $invoice_page['number'] = $invoice->getInvoiceNumber();
  $invoice_page['status_message'] = $invoice->getStatusMessage();
  $invoice_page['status'] = $invoice->getStatus() ? $invoice::getStatusName($invoice->getStatus()) : '';
  $invoice_page['date_created'] = $invoice->getCreatedDate();
  $invoice_page['amount'] = $invoice->getAmount();
  $vars['invoice_page'] = $invoice_page;

}