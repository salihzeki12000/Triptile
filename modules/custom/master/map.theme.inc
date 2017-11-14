<?php

/**
 * Implements template_proprocess_hook
 */
function template_preprocess_map(&$vars) {
  $vars['#attached']['library'][] = 'master/map';
  $vars['#attached']['drupalSettings']['routes'] = $vars['routes'];
}