<?php

namespace Drupal\train_base\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Train entities.
 */
class TrainViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['train']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Train'),
      'help' => $this->t('The Train ID.'),
    );

    $data['train']['average_rating'] = [
      'title' => $this->t('Average rating'),
      'help' => $this->t('Calculated train average rating.'),
      'field' => [
        'id' => 'train_base_train_average_rating',
        'click sortable' => false,
      ],
    ];

    $data['train']['translation_languages'] = [
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
