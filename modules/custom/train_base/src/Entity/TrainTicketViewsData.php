<?php

namespace Drupal\train_base\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Train ticket entities.
 */
class TrainTicketViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['train_ticket']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Train ticket'),
      'help' => $this->t('The Train ticket ID.'),
    );

    return $data;
  }

}
