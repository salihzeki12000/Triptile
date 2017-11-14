<?php

/**
 * @file
 * Contains station.page.inc.
 *
 * Page callback for Station entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Station templates.
 *
 * Default template: station.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_station(array &$variables) {
  // Fetch Station Entity Object.
  $station = $variables['elements']['#station'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
