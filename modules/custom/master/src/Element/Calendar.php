<?php
/**
 * @file
 * Contains \Drupal\master\Element\Calendar.
 */
 
namespace Drupal\master\Element;
 
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
 
/**
 * Provides an calendar element.
 *
 * @RenderElement("calendar")
 */
class Calendar extends FormElement {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      //'#theme' => 'calendar',

      '#input' => TRUE,
      '#label' => 'Calendar',
      '#attached' => [
        'library' => ['master/calendar'],
      ],
      '#process' => [
        [$class, 'processCalendar'],
      ],
      '#pre_render' => [
        [$class, 'preRenderCalendar'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processCalendar(&$element, FormStateInterface $form_state, &$complete_form) {
    return $element;
  }

  /**
   * Prepare the render array for the template.
   */
  public static function preRenderCalendar($element) {
    $element['date'] = [
      '#type' => 'textfield',
      '#title' => !empty($element['#title']) ? $element['#title'] : '',
      '#id' => $element['#id'],
      '#attributes' => [
        'name' => $element['#name'],
        'class' => [
          'element-calendar',
        ]
      ],
    ];

    return $element;
  }

}