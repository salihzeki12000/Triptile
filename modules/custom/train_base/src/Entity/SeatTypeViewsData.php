<?php

namespace Drupal\train_base\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Seat type entities.
 */
class SeatTypeViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['seat_type']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Seat type'),
      'help' => $this->t('The Seat type ID.'),
    );

    $data['seat_type']['translation_languages'] = [
      'title' => $this->t('All translation languages'),
      'help' => $this->t('Return the languages for which entity is translated.'),
      'field' => [
        'id' => 'master_translation_languages',
        'click sortable' => false,
      ],
    ];

    return $data;
  }

}
