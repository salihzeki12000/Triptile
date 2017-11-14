<?php

/**
 * @file
 * Contains hub.page.inc.
 *
 * Page callback for Hub entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Hub templates.
 *
 * Default template: hub.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_hub(array &$variables) {
  // Fetch Hub Entity Object.
  $hub = $variables['elements']['#hub'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
