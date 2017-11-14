<?php

/**
 * @file
 * Theme settings.
 */

/**
 * Implementation of hook_form_system_theme_settings_alter()
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   A keyed array containing the current state of the form.
 */
function basic_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  // Get theme config.
  $active_theme = \Drupal::theme()->getActiveTheme();
  $theme = $active_theme->getName();
  $config = \Drupal::config($theme . '.settings');

  // Create instance of request path from ConditionBasePlugin.
  /* @var \Drupal\system\Plugin\Condition\RequestPath $request_path */
  $request_path = \Drupal::service('plugin.manager.condition')->createInstance('request_path');

  // Add custom elements to form.
  $form['basic_settings']['theme_custom_templates'] = [
    '#type' => 'details',
    '#open' => false,
    '#title' => t('Theme custom templates'),
    '#description' => t('You can choose path options for each custom template.'),
  ];

  $templates = _basic_get_template_scripts(_basic_get_path_to_scripts());
  foreach ($templates as $region_name => $region_templates) {
    foreach ($region_templates as $template) {
      $template_name = _basic_replace_dots($template['name']);
      $form['basic_settings']['theme_custom_templates'][$template_name] = [
        '#type' => 'details',
        '#tree' => true,
        '#open' => false,
        '#title' => t($template['name']),
      ];
      $default_values = $config->get($template_name) ? : [];
      $request_path->setConfiguration($default_values);
      $form['basic_settings']['theme_custom_templates'][$template_name] = $request_path->buildConfigurationForm($form['basic_settings']['theme_custom_templates'][$template_name], $form_state);
      $negate_default_value = (int) $form['basic_settings']['theme_custom_templates'][$template_name]['negate']['#default_value'];
      $form['basic_settings']['theme_custom_templates'][$template_name]['negate']['#type'] = 'radios';
      $form['basic_settings']['theme_custom_templates'][$template_name]['negate']['#default_value'] = $negate_default_value;
      $form['basic_settings']['theme_custom_templates'][$template_name]['negate']['#title_display'] = 'invisible';
      $form['basic_settings']['theme_custom_templates'][$template_name]['negate']['#options'] = [
        t('Show for the listed pages'),
        t('Hide for the listed pages'),
      ];
    }
  }
}


/**
 * Form validation handler for the theme settings form.
 */
/* -- Delete this line to enable.
function basic_settings_form_validate($form, &$form_state) {

}
// */


/**
 * Form submit handler for the theme settings form.
 */
 /* -- Delete this line to enable.
function basic_settings_form_submit($form, &$form_state) {

}
// */
