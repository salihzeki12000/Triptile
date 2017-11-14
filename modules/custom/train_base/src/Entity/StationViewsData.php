<?php

namespace Drupal\train_base\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Station entities.
 */
class StationViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['station']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Station'),
      'help' => $this->t('The Station ID.'),
    );

    $data['station']['translation_languages'] = [
      'title' => $this->t('All translation languages'),
      'help' => $this->t('Return the languages for which entity is translated.'),
      'field' => [
        'id' => 'master_translation_languages',
        'click sortable' => false,
      ],
    ];

    // Alter views data
    // ::getViewsData works incorrectly fo multiple BaseDefinitionField
    // @todo need to find/create issue on the drupal.org and resolve it.
    $station_name = $data['station__name']['name'];
    unset($data['station__name']['name']);
    $data['station__name']['name_value'] = $station_name;

    return $data;
  }

}
