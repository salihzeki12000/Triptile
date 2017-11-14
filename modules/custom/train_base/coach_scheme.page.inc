<?php

/**
 * @file
 * Contains coach_scheme.page.inc.
 *
 * Page callback for Coach scheme entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Coach scheme templates.
 *
 * Default template: coach_scheme.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_coach_scheme(array &$variables) {
  // Fetch CoachScheme Entity Object.
  $coach_scheme = $variables['elements']['#coach_scheme'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
