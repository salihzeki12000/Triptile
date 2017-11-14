<?php

namespace Drupal\store\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Store order entities.
 */
class StoreOrderViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['store_order']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Store order'),
      'help' => $this->t('The Store order ID.'),
    );

    $data['store_order']['state'] = [
      'title' => $this->t('Order state'),
      'help' => $this->t('Order state for user. Use it instead of Order status to display status to user.'),
      'field' => [
        'id' => 'store_store_order_state',
        'click sortable' => false,
      ],
    ];

    return $data;
  }

}
