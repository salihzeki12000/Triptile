<?php

namespace Drupal\local_train_provider\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Timetable entry entities.
 */
class TimetableEntryViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['timetable_entry']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Timetable entry'),
      'help' => $this->t('The Timetable entry ID.'),
    );

    return $data;
  }

}
