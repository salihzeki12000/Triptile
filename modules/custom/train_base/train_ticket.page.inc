<?php

/**
 * @file
 * Contains train_ticket.page.inc.
 *
 * Page callback for Train ticket entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Train ticket templates.
 *
 * Default template: train_ticket.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_train_ticket(array &$variables) {
  // Fetch TrainTicket Entity Object.
  $train_ticket = $variables['elements']['#train_ticket'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
