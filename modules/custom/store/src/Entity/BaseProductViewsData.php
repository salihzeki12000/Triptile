<?php

namespace Drupal\store\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Base product entities.
 */
class BaseProductViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['base_product']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Base product'),
      'help' => $this->t('The Base product ID.'),
    );

    $data['base_product']['translation_languages'] = [
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
