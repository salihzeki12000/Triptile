<?php

/**
 * @file
 * Contains hotel.page.inc.
 *
 * Page callback for Hotel entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Hotel templates.
 *
 * Default template: hotel.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_hotel(array &$variables) {
  // Fetch Hotel Entity Object.
  $hotel = $variables['elements']['#hotel'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
