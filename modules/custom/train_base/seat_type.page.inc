<?php

/**
 * @file
 * Contains seat_type.page.inc.
 *
 * Page callback for Seat type entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Seat type templates.
 *
 * Default template: seat_type.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_seat_type(array &$variables) {
  // Fetch SeatType Entity Object.
  $seat_type = $variables['elements']['#seat_type'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
