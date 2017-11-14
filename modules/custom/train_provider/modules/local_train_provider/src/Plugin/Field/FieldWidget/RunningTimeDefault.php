<?php

namespace Drupal\local_train_provider\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDatelistWidget;

/**
 * Plugin implementation of the 'running_time_default' widget.
 *
 * @FieldWidget(
 *   id = "running_time_default",
 *   label = @Translation("Departure time"),
 *   field_types = {
 *     "integer",
 *     "running_time"
 *   }
 * )
 */
class RunningTimeDefault extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $days = range(0, 20);
    $hours = range(0, 23);
    $minutes = range(0, 59);

    if (isset($items[$delta]->running_time)) {
      $seconds = $items[$delta]->running_time;
      $d = (int)floor($seconds / (3600 * 24));
      $h = (int)floor(($seconds % (3600 * 24)) / 3600);
      $m = (int)floor((($seconds % (3600 * 24)) % 3600) / 60);
    }

    $element['running_time'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['running-time-wrapper']
      ]
    ];
    $element['running_time']['days'] = [
      '#title' => $this->t('Running time'),
      '#type' => 'select',
      '#options' => $days,
      '#default_value' => isset($d) ? $d : NULL,
      '#suffix' => $this->t('Days'),
      '#required' => $element['#required'],
    ];

    $element['running_time']['hours'] = [
      '#type' => 'select',
      '#title' => $this->t('Running time: minutes'),
      '#title_display' => 'hidden',
      '#options' => $hours,
      '#default_value' => isset($h) ? $h : NULL,
      '#suffix' => $this->t('Hours'),
      '#required' => $element['#required'],
    ];

    $element['running_time']['minutes'] = [
      '#title' => $this->t('Departure time: minutes'),
      '#title_display' => 'hidden',
      '#type' => 'select',
      '#options' => $minutes,
      '#default_value' => isset($m) ? $m : NULL,
      '#suffix' => $this->t('Minutes'),
      '#required' => $element['#required'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $item) {
      $days_in_seconds = 24 * 3600 * $item['running_time']['days'];
      $hours_in_seconds = 3600 * $item['running_time']['hours'];
      $minutes_in_seconds = 60 * $item['running_time']['minutes'];
      $values[$key]['running_time'] = $days_in_seconds + $hours_in_seconds + $minutes_in_seconds;
    }

    return $values;
  }
}
