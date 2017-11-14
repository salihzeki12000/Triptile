<?php

/**
 * @file
 * Contains passenger.page.inc.
 *
 * Page callback for Passenger entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Passenger templates.
 *
 * Default template: passenger.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_passenger(array &$variables) {
  // Fetch Passenger Entity Object.
  $passenger = $variables['elements']['#passenger'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
