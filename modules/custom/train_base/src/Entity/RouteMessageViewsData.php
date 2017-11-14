<?php

namespace Drupal\train_base\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Route message entities.
 */
class RouteMessageViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['route_message']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Route message'),
      'help' => $this->t('The Route message ID.'),
    );

    return $data;
  }

}
