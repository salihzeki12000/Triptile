<?php

/**
 * @file
 * Contains express3_station.page.inc.
 *
 * Page callback for Express3station entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Express3station templates.
 *
 * Default template: express3_station.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_express3_station(array &$variables) {
  // Fetch Express3Station Entity Object.
  $express3_station = $variables['elements']['#express3_station'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
