<?php

namespace Drupal\local_train_provider\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'schedule_default' formatter.
 *
 * @FieldFormatter(
 *   id = "schedule_default",
 *   label = @Translation("Schedule formatter default"),
 *   field_types = {
 *     "schedule"
 *   }
 * )
 */
class ScheduleDefault extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_schedule' => 'all',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['display_schedule'] = [
      '#type' => 'select',
      '#title' => t('Configure display schedule', [], ['context' => 'ScheduleDefault']),
      '#options' => [
        'availability' => t('Availability', [], ['context' => 'ScheduleDefault']),
        'schedule'     => t('Schedule',     [], ['context' => 'ScheduleDefault']),
        'all'          => t('All',          [], ['context' => 'ScheduleDefault']),
      ],
      '#default_value' => $this->getSetting('display_schedule')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $weekdays_options = [
      1  => t('Mon', [], ['context' => 'ScheduleDefault']),
      2  => t('Tue', [], ['context' => 'ScheduleDefault']),
      4  => t('Wed', [], ['context' => 'ScheduleDefault']),
      8  => t('Thu', [], ['context' => 'ScheduleDefault']),
      16 => t('Fri', [], ['context' => 'ScheduleDefault']),
      32 => t('Sat', [], ['context' => 'ScheduleDefault']),
      64 => t('Sun', [], ['context' => 'ScheduleDefault']),
    ];
    $display_schedule = $this->getSetting('display_schedule');

    // @todo need refactoring.
    foreach ($items as $delta => $item) {
      $markup = '';

      if ($display_schedule !== 'schedule') {
        $available_until = $available_from = '';
        if ($item->available_from) {
          $available_from = $item->available_from;
        }
        if ($item->available_until) {
          $available_until = $item->available_until;
        }
        if ($item->available_from && $item->available_until) {
          $result_available = '<p>' . $available_from . ' - ' . $available_until . '</p>';
        } else {
          $result_available = '<p>' . $available_from . $available_until . '</p>';
        }
      }
      if ($display_schedule !== 'availability') {
        if ($item->even_days) {
          if ($item->even_days == 1) {
            $result_schedule = '<p>' . t('Odd', [], ['context' => 'ScheduleDefault']) . '</p>';
          } else {
            $result_schedule = '<p>' . t('Even', [], ['context' => 'ScheduleDefault']) . '</p>';
          }
        }
        if ($item->every_n_days) {
          $result_schedule = '<p>' . t('Every @every_n_days day', ['@every_n_days' => $item->every_n_days], ['context' => 'ScheduleDefault']) . '</p>';
        }
        if ($item->weekdays) {
          if ($item->weekdays == 127) {
            $result_schedule = '<p>' . t('Every day', [], ['context' => 'ScheduleDefault']) . '</p>';
          } else {
            $weekdays = [];
            foreach ($weekdays_options as $key => $value) {
              if (($items->weekdays & $key) == $key) {
                $weekdays[] = $value;
              }
            }
            $result_schedule = implode(', ', $weekdays);
          }
        }
      }
      if ($display_schedule == 'availability'){
        $markup = $result_available;
      }
      if ($display_schedule == 'schedule'){
        $markup = $result_schedule;
      }
      if ($display_schedule == 'all'){
        $markup = $result_schedule . $result_available;
      }

      $elements[$delta] = ['#markup' => $markup];
    }

    return $elements;
  }

}
