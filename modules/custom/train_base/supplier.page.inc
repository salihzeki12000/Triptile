<?php

/**
 * @file
 * Contains supplier.page.inc.
 *
 * Page callback for Supplier entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Supplier templates.
 *
 * Default template: supplier.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_supplier(array &$variables) {
  // Fetch Supplier Entity Object.
  $supplier = $variables['elements']['#supplier'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
