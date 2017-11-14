<?php

namespace Drupal\train_booking\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Failed search entities.
 */
class FailedSearchViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['failed_search']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Failed search'),
      'help' => $this->t('The Failed search ID.'),
    );

    return $data;
  }

}
