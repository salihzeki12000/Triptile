<?php

namespace Drupal\store\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Customer profile entities.
 */
class CustomerProfileViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['customer_profile']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Customer profile'),
      'help' => $this->t('The Customer profile ID.'),
    );

    return $data;
  }

}
