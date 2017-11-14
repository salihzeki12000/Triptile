<?php

namespace Drupal\train_base\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Train class entities.
 */
class TrainClassViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['train_class']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Train class'),
      'help' => $this->t('The Train class ID.'),
    );

    $data['train_class']['translation_languages'] = [
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
