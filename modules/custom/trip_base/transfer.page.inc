<?php

/**
 * @file
 * Contains transfer.page.inc.
 *
 * Page callback for Transfer entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Transfer templates.
 *
 * Default template: transfer.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_transfer(array &$variables) {
  // Fetch Transfer Entity Object.
  $transfer = $variables['elements']['#transfer'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
