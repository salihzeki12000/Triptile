<?php

/**
 * @file
 * Contains store_order.page.inc.
 *
 * Page callback for Store order entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Store order templates.
 *
 * Default template: store_order.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_store_order(array &$variables) {
  // Fetch StoreOrder Entity Object.
  $store_order = $variables['elements']['#store_order'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
