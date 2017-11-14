<?php

/**
 * @file
 * Contains success_search_detailed.page.inc.
 *
 * Page callback for Detailed success search entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Detailed success search templates.
 *
 * Default template: success_search_detailed.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_success_search_detailed(array &$variables) {
  // Fetch SuccessSearchDetailed Entity Object.
  $success_search_detailed = $variables['elements']['#success_search_detailed'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
