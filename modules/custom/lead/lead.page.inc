<?php

/**
 * @file
 * Contains lead.page.inc.
 *
 * Page callback for Lead entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Lead templates.
 *
 * Default template: lead.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_lead(array &$variables) {
  // Fetch Lead Entity Object.
  $lead = $variables['elements']['#lead'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
