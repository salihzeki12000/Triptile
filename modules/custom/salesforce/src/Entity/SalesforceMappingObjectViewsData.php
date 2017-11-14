<?php

namespace Drupal\salesforce\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Salesforce mapping object entities.
 */
class SalesforceMappingObjectViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['salesforce_mapping_object']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Salesforce mapping object'),
      'help' => $this->t('The Salesforce mapping object ID.'),
    );

    return $data;
  }

}
