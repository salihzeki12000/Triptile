<?php

/**
 * @file
 * Contains order_item.page.inc.
 *
 * Page callback for Order item entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Order item templates.
 *
 * Default template: order_item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_order_item(array &$variables) {
  // Fetch OrderItem Entity Object.
  $order_item = $variables['elements']['#order_item'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
