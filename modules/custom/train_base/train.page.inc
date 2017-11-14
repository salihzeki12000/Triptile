<?php

/**
 * @file
 * Contains train.page.inc.
 *
 * Page callback for Train entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Train templates.
 *
 * Default template: train.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_train(array &$variables) {
  // Fetch Train Entity Object.
  $train = $variables['elements']['#train'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
