<?php

namespace Drupal\express3_train_provider\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Express3station entities.
 */
class Express3StationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
