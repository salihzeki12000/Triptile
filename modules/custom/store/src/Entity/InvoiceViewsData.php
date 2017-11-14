<?php

namespace Drupal\store\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Invoice entities.
 */
class InvoiceViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['invoice']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Invoice'),
      'help' => $this->t('The Invoice ID.'),
    );

    return $data;
  }

}
