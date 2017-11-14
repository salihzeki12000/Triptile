<?php

namespace Drupal\train_base\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Coach class entities.
 */
class CoachClassViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['coach_class']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Coach class'),
      'help' => $this->t('The Coach class ID.'),
    );

    $data['coach_class']['translation_languages'] = [
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
