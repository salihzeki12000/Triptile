<?php

namespace Drupal\master\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a selectized form element.
 *
 * @FormElement("selectize")
 */
class Selectize extends FormElement {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return array(
      '#input' => true,
      '#multiple' => false,
      '#process' => array(
        array($class, 'processSelectize'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderSelectize'),
      ),
      //'#theme_wrappers' => array('form_element'),
      '#theme' => 'selectize',
      '#settings' => self::settings(),
      '#options_callback' => false,
    );
  }

  /**
   * Prepares a #type 'selectize' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderSelectize($element) {
    $attributes = $element['#attributes'];
    $attributes['name'] = $element['#name'];
    $attributes['class'][] = 'element-selectize';
    $element['selectize'] = [
      '#type' => 'select',
      '#title' => !empty($element['#title']) ? $element['#title'] : '',
      '#id' => $element['#id'],
      '#options' => !empty($element['#options']) ? $element['#options'] : [],
      '#default_value' => !empty($element['#default_value']) ? $element['#default_value'] : NULL,
      '#attributes' => $attributes,
    ];

    if (isset($element['#states'])) {
      $element['selectize']['#states'] = $element['#states'];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function processSelectize(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#settings'] = self::settings($element['#settings']);

    // @todo create service for it.
    $mobileDetector = new \Mobile_Detect();
    if ($mobileDetector->isMobile()) {
      $element['#settings']['isMobile'] = true;
      if ($element['#settings']['displayOnMobile'] == FALSE) {
        $element['#options'] = $element['#settings']['options'];
        if (!empty($element['#settings']['empty_option'] )) {
          $element['#empty_option'] = $element['#settings']['empty_option'];
        }
      }
    }

    if (!empty($element['#value'])) {
      $element['#settings']['value'] = $element['#value'];
    }

    $included = false;
    $options = [];
    if (!empty($element['#settings']['options'])) {
      $submit_value = $form_state->getValue($element['#parents']);
      foreach ($element['#settings']['options'] as $key => $value) {
        if ($submit_value == $key) {
          $included = true;
        }
        $options[] = [
          $element['#settings']['valueField'] => $key,
          $element['#settings']['labelField'] => $value,
        ];
      }
    }
    if (!$included && $element['#options_callback']) {
      $submit_value = $form_state->getValue($element['#parents']);
      $additional_options = call_user_func($element['#options_callback'], [$submit_value => $submit_value]);
      if (isset($additional_options[$submit_value])) {
        $options[] = [
          $element['#settings']['valueField'] => $submit_value,
          $element['#settings']['labelField'] => $additional_options[$submit_value],
        ];
      }
    }
    $element['#settings']['options'] = $options;

    // @todo need for clear working with ajax.
    $element['#attached']['drupalSettings']['selectize'][$element['#id']] = json_encode($element['#settings']);

    // if drag_drop plugin is requested, we need to load the sortable plugin.
    if (isset($element['#settings']['plugins']) && in_array('drag_drop', $element['#settings']['plugins'])) {
      $complete_form['#attached']['library'][] = 'core/jquery.ui.sortable';
    }

    // inject the selectize library and CSS assets.
    $complete_form['#attached']['library'][] = 'master/selectize-core';
    $complete_form['#attached']['library'][] = 'master/selectize-drupal';

    // #multiple select fields need a special #name.
    if (!empty($element['#multiple'])) {
      $element['#attributes']['multiple'] = 'multiple';
      $element['#attributes']['name'] = $element['#name'] . '[]';
    }
    // A non-#multiple select needs special handling to prevent user agents from
    // preselecting the first option without intention. #multiple select lists do
    // not get an empty option, as it would not make sense, user interface-wise.
    else {
      // If the element is set to #required through #states, override the
      // element's #required setting.
      $required = isset($element['#states']['required']) ? true : $element['#required'];
      // If the element is required and there is no #default_value, then add an
      // empty option that will fail validation, so that the user is required to
      // make a choice. Also, if there's a value for #empty_value or
      // #empty_option, then add an option that represents emptiness.
      if (($required && !isset($element['#default_value'])) || isset($element['#empty_value']) || isset($element['#empty_option'])) {
        $element += array(
          '#empty_value' => '',
          '#empty_option' => $required ? t('- Select -') : t('- None -'),
        );
        // The empty option is prepended to #options and purposively not merged
        // to prevent another option in #options mistakenly using the same value
        // as #empty_value.
        $empty_option = array($element['#empty_value'] => $element['#empty_option']);
        $element['#options'] = !isset($element['#options']) ? [] : $element['#options'];
        $element['#options'] = $empty_option + $element['#options'];
      }
    }

    if ($mobileDetector->isMobile() && $element['#settings']['displayOnMobile'] == FALSE) {
      if (empty($element['#value'])) {
        $element['#value'] = 0;
      }
    }
    else {
      $submit_value = $form_state->getValue($element['#parents']);
      if (!empty($submit_value)) {
        $element['#options'] = [
          $submit_value => $submit_value,
        ];
      }
    }

    // @todo Add error class to select from selectize.

    return $element;
  }

  /**
   * Return default settings for Selectize. Pass in values to override defaults.
   * @param $values
   * @return array
   */
  public static function settings(array $values = array()) {
    $settings = array(
      'maxItems' => 1,
      'selectOnTab' => true,
      'clearOnSearch' => true,
      'displayOnMobile' => true,
      'valueField' => 'id',
      'labelField' => 'name',
      'searchField' => 'name',
      'create' => false,
    );

    return array_merge($settings, $values);
  }
}