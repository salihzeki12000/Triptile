<?php

/**
 * @file
 * Contains failed_search.page.inc.
 *
 * Page callback for Failed search entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Failed search templates.
 *
 * Default template: failed_search.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_failed_search(array &$variables) {
  // Fetch FailedSearch Entity Object.
  $failed_search = $variables['elements']['#failed_search'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
