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
 * Prepares variables for Invoice templates.
 *
 * Default template: invoice.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_invoice(array &$variables) {
  // Fetch Invoice Entity Object.
  $invoice = $variables['elements']['#invoice'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
