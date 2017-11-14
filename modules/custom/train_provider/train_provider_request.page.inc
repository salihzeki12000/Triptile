<?php

/**
 * @file
 * Contains train_provider_request.page.inc.
 *
 * Page callback for Booking stat entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Search statistic templates.
 *
 * Default template: train_provider_request.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_booking_stat(array &$variables) {
  // Fetch SearchStatic Entity Object.
  $train_provider_request = $variables['elements']['#train_provider_request'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
