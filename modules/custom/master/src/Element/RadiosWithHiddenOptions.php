<?php

namespace Drupal\master\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;
use Drupal\Core\Url;

/**
 * Provides a form input element for rendering radios split into 2 parts.
 *
 * Second part is hidden by default and can be displayed by click on the link.
 * Options #visible_count is required. If it is not set, magic will not happen.
 *
 * Example usage:
 * @code
 * $form['options'] = [
 *   '#type' => 'radios_with_hidden_options',
 *   '#title' => $this->t('Options'),
 *   '#options' => [0 => 'Option 1', 1 => 'Option 2', 2 => 'Option 3', 3 => 'Option 4'],
 *   '#open_link_title' => 'more options',
 *   '#hide_link_title' => 'less options',
 *   '#visible_count' => 3,
 * ];
 * @endcode
 *
 * @FormElement("radios_with_hidden_options")
 */
class RadiosWithHiddenOptions extends Radios {

  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processRadios($element, $form_state, $complete_form);

    if (isset($element['#visible_count']) && $element['#visible_count'] < count($element['#options'])) {
      $i = 0;
      $firstVisibleOptionId = null;
      $firstHiddenOptionId = null;
      foreach ($element['#options'] as $key => $choice) {
        if ($i < $element['#visible_count']) {
          if (!$firstVisibleOptionId) {
            $firstVisibleOptionId = $element[$key]['#id'];
          }
          $element[$key]['#wrapper_attributes']['class'][] = 'radios-visible';
        }
        else {
          if (!$firstHiddenOptionId) {
            $firstHiddenOptionId = $element[$key]['#id'];
          }
          $element[$key]['#wrapper_attributes']['class'][] = 'radios-hidden';
        }
        $i++;
      }

      $element['more_options'] = [
        '#type' => 'link',
        '#title' => isset($element['#open_link_title']) ? $element['#open_link_title'] : t('more options'),
        '#url' => Url::fromUserInput('#' . $firstHiddenOptionId),
        '#attributes' => ['class' => ['toggle-options', 'more-options']],
      ];
      $element['less_options'] = [
        '#type' => 'link',
        '#title' => isset($element['#hide_link_title']) ? $element['#hide_link_title'] : t('less options'),
        '#url' => Url::fromUserInput('#' . $firstVisibleOptionId),
        '#attributes' => ['class' => ['toggle-options', 'less-options']],
      ];

      $element['#attached']['library'][] = 'master/radios-with-hidden-options';
    }

    return $element;
  }

}
