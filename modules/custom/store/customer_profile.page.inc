<?php

/**
 * @file
 * Contains customer_profile.page.inc.
 *
 * Page callback for Customer profile entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Customer profile templates.
 *
 * Default template: customer_profile.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_customer_profile(array &$variables) {
  // Fetch CustomerProfile Entity Object.
  $customer_profile = $variables['elements']['#customer_profile'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
