<?php

/**
 * @file
 * Contains car_service.page.inc.
 *
 * Page callback for Car service entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Car service templates.
 *
 * Default template: car_service.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_car_service(array &$variables) {
  // Fetch CarService Entity Object.
  $car_service = $variables['elements']['#car_service'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
