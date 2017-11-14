<?php

/**
 * @file
 * Contains transaction.page.inc.
 *
 * Page callback for Transaction entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Transaction templates.
 *
 * Default template: transaction.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_transaction(array &$variables) {
  // Fetch Transaction Entity Object.
  $transaction = $variables['elements']['#transaction'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
