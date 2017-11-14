<?php

/**
 * @file
 * Contains coach_class.page.inc.
 *
 * Page callback for Coach class entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Coach class templates.
 *
 * Default template: coach_class.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_coach_class(array &$variables) {
  // Fetch CoachClass Entity Object.
  $coach_class = $variables['elements']['#coach_class'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
