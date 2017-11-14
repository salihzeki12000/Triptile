<?php

namespace Drupal\payment\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Transaction entities.
 */
class TransactionViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['transaction']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Transaction'),
      'help' => $this->t('The Transaction ID.'),
    );

    return $data;
  }

}
