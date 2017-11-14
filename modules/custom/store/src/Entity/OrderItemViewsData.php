<?php

namespace Drupal\store\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Order item entities.
 */
class OrderItemViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['order_item']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Order item'),
      'help' => $this->t('The Order item ID.'),
    );

    return $data;
  }

}
