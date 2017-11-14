<?php

namespace Drupal\train_base\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Car service entities.
 */
class CarServiceViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['car_service']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Car service'),
      'help' => $this->t('The Car service ID.'),
    );

    return $data;
  }

}
