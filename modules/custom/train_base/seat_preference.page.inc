<?php

/**
 * @file
 * Contains seat_preference.page.inc.
 *
 * Page callback for Seat preference entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Seat preference templates.
 *
 * Default template: seat_preference.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_seat_preference(array &$variables) {
  // Fetch SeatPreference Entity Object.
  $seat_preference = $variables['elements']['#seat_preference'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
