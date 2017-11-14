<?php

namespace Drupal\local_train_provider\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDatelistWidget;

/**
 * Plugin implementation of the 'departure_time_default' widget.
 *
 * @FieldWidget(
 *   id = "departure_time_default",
 *   label = @Translation("Departure time"),
 *   field_types = {
 *     "integer",
 *     "departure_time"
 *   }
 * )
 */
class DepartureTimeDefault extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $ten = [
      0 => '00',
      1 => '01',
      2 => '02',
      3 => '03',
      4 => '04',
      5 => '05',
      6 => '06',
      7 => '07',
      8 => '08',
      9 => '09',
    ];
    $hours = array_merge($ten, range(10, 23));
    $minutes = array_merge($ten, range(10, 59));

    if (isset($items[$delta]->departure_time)) {
      $seconds = $items[$delta]->departure_time;
      $h = (int)floor($seconds / 3600);
      $m = (int)floor(($seconds % 3600) / 60);
    }

    $element['departure_time'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['departure-time-wrapper']
      ]
    ];

    $element['departure_time']['hours'] = [
      '#title' => $this->t('Departure time'),
      '#type' => 'select',
      '#options' => $hours,
      '#default_value' => isset($h) ? $h : NULL,
      '#suffix' => ':',
      '#required' => $element['#required'],
    ];

    $element['departure_time']['minutes'] = [
      '#title' => $this->t('Departure time: minutes'),
      '#title_display' => 'hidden',
      '#type' => 'select',
      '#options' => $minutes,
      '#default_value' => isset($m) ? $m : NULL,
      '#required' => $element['#required'],
    ];
    $element['#attached']['library'][] = 'local_train_provider/global-styling';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $item) {
      $hours_in_seconds = 3600 * $item['departure_time']['hours'];
      $minutes_in_seconds = 60 * $item['departure_time']['minutes'];
      $values[$key]['departure_time'] = $hours_in_seconds + $minutes_in_seconds;
    }

    return $values;
  }
}
