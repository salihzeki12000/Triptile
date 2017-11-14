<?php

/**
 * @file
 * Contains booking_stat.page.inc.
 *
 * Page callback for Booking stat entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Booking stat templates.
 *
 * Default template: booking_stat.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_booking_stat(array &$variables) {
  // Fetch BookingStat Entity Object.
  $booking_stat = $variables['elements']['#booking_stat'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
