<?php

namespace Drupal\local_train_provider\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'running_time_default' formatter.
 *
 * @FieldFormatter(
 *   id = "running_time_default",
 *   label = @Translation("Running Time formatter default"),
 *   field_types = {
 *     "integer",
 *     "running_time"
 *   }
 * )
 */
class RunningTimeDefault extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $d = $h = $m = 0;

    if (isset($items->running_time)) {
      $seconds = $items->running_time;
      $d = (int)floor($seconds / (3600 * 24));
      $h = (int)floor(($seconds % (3600 * 24)) / 3600);
      $m = (int)floor((($seconds % (3600 * 24)) % 3600) / 60);
    }

    $days_text_element = $this->formatPlural($d, '1 day', '@count days');
    $hours_text_element = $this->formatPlural($h, '1 hour', '@count hours');
    $minutes_text_element = $this->formatPlural($m, '1 minute', '@count minutes');

    $elements[] = ['#markup' => $days_text_element . ' ' . $hours_text_element . ' ' . $minutes_text_element];

    return $elements;
  }

}
