<?php

/**
 * @file
 * Contains route_message.page.inc.
 *
 * Page callback for Route message entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Route message templates.
 *
 * Default template: route_message.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_route_message(array &$variables) {
  // Fetch RouteMessage Entity Object.
  $route_message = $variables['elements']['#route_message'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
