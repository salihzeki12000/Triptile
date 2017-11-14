<?php

/**
 * @file
 * Contains trip.page.inc.
 *
 * Page callback for Trip entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Trip templates.
 *
 * Default template: trip.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_trip(array &$variables) {
  // Fetch Trip Entity Object.
  $trip = $variables['elements']['#trip'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
