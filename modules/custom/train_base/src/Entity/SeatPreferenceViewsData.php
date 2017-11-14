<?php

namespace Drupal\train_base\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Seat preference entities.
 */
class SeatPreferenceViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['seat_preference']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Seat preference'),
      'help' => $this->t('The Seat preference ID.'),
    );

    // Alter views data
    // ::getViewsData works incorrectly fo multiple BaseDefinitionField
    // @todo need to find/create issue on the drupal.org and resolve it.
    $supplier = $data['seat_preference__supplier']['supplier'];
    unset($data['seat_preference__supplier']['supplier']);
    $data['seat_preference__supplier']['supplier_target_id'] = $supplier;

    $carService = $data['seat_preference__car_service']['car_service'];
    unset($data['seat_preference__car_service']['car_service']);
    $data['seat_preference__car_service']['car_service_target_id'] = $carService;

    $seatType = $data['seat_preference__seat_type']['seat_type'];
    unset($data['seat_preference__seat_type']['seat_type']);
    $data['seat_preference__seat_type']['seat_type_target_id'] = $seatType;

    return $data;
  }

}
