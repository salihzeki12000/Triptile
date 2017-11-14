<?php

namespace Drupal\train_base\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Coach scheme entities.
 */
class CoachSchemeViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['coach_scheme']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Coach scheme'),
      'help' => $this->t('The Coach scheme ID.'),
    );

    return $data;
  }

}
