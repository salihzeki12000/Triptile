<?php

/**
 * @file
 * Contains timetable_entry.page.inc.
 *
 * Page callback for Timetable entry entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Timetable entry templates.
 *
 * Default template: timetable_entry.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_timetable_entry(array &$variables) {
  // Fetch TimetableEntry Entity Object.
  $timetable_entry = $variables['elements']['#timetable_entry'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
