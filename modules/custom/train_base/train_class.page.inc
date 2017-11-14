<?php

/**
 * @file
 * Contains train_class.page.inc.
 *
 * Page callback for Train class entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Train class templates.
 *
 * Default template: train_class.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_train_class(array &$variables) {
  // Fetch TrainClass Entity Object.
  $train_class = $variables['elements']['#train_class'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
