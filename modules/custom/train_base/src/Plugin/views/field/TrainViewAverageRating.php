<?php

namespace Drupal\train_base\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Class TrainViewAverageRating
 *
 * @ViewsField("train_base_train_average_rating")
 * // @todo Can we use general class for all computed fields?
 */
class TrainViewAverageRating extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\train_base\Entity\Train $train */
    $train = $this->getEntity($values);
    return $train->getAverageRating();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing since the field is computed.
  }

}
