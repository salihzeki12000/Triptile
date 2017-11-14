<?php

namespace Drupal\local_train_provider\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'departure_time_default' formatter.
 *
 * @FieldFormatter(
 *   id = "departure_time_default",
 *   label = @Translation("Departure Time formatter default"),
 *   field_types = {
 *     "integer",
 *     "departure_time"
 *   }
 * )
 */
class DepartureTimeDefault extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $date = new DrupalDateTime();
    $date->setTimezone(new \DateTimeZone('UTC'));
    foreach ($items as $delta => $item) {
      $date->setTimestamp($item->departure_time);

      $elements[$delta] = ['#markup' => $date->format('H:i')];
    }
    return $elements;
  }

}
