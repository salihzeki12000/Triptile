<?php

namespace Drupal\master\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a spinner form element.
 *
 * @FormElement("spinner")
 */
class Spinner extends FormElement {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return array(
      '#input' => TRUE,
      '#process' => array(
        array($class, 'processSpinner'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderSpinner'),
      ),
      '#theme' => 'spinner',
      '#settings' => self::settings(),
    );
  }

  /**
   * Prepares a #type 'spinner' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderSpinner($element) {
    $attributes = $element['#attributes'];
    $attributes['name'] = $element['#name'];
    $attributes['class'][] = 'element-spinner';
    $element['number'] = [
      '#type' => 'textfield',
      '#title' => !empty($element['#title']) ? $element['#title'] : '',
      '#id' => $element['#id'],
      '#default_value' => !empty($element['#default_value']) ? $element['#default_value'] : NULL,
      '#attributes' => $attributes,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function processSpinner(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#settings'] = self::settings($element['#settings']);
    $complete_form['#attached']['drupalSettings']['spinner'][$element['#id']] = json_encode($element['#settings']);

    // inject the spin library and CSS assets.
    $complete_form['#attached']['library'][] = 'master/spinner';

    return $element;
  }

  /**
   * Return default settings for spinner. Pass in values to override defaults.
   * @param $values
   * @return array
   */
  public static function settings(array $values = array()) {
    $settings = array(
      'min' => 0,
    );

    return array_merge($settings, $values);
  }
}