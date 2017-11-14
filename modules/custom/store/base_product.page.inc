<?php

/**
 * @file
 * Contains base_product.page.inc.
 *
 * Page callback for Base product entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Base product templates.
 *
 * Default template: base_product.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_base_product(array &$variables) {
  // Fetch BaseProduct Entity Object.
  $base_product = $variables['elements']['#base_product'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
