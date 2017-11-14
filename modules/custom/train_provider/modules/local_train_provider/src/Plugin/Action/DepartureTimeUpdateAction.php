<?php

namespace Drupal\local_train_provider\Plugin\Action;

use Drupal\master\Action\ViewsBulkOperationsActionBase;

/**
 * Update field 'departure_time'.
 *
 * @Action(
 *   id = "departure_time_update_action",
 *   label = @Translation("Update departure time"),
 *   type = "timetable_entry"
 * )
 */
class DepartureTimeUpdateAction extends ViewsBulkOperationsActionBase{

  public function getEntityBundle() {
    return 'default';
  }

  public function getFieldName() {
    return 'departure_time';
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity) {
      /** @var \Drupal\local_train_provider\Entity\TimetableEntry $timetableEntry */
      $timetableEntry = $entity;
      $hoursInSeconds = 3600 * $this->configuration['value'][0]['departure_time']['hours'];
      $minutesInSeconds = 60 * $this->configuration['value'][0]['departure_time']['minutes'];
      $timetableEntry->set('departure_time', $hoursInSeconds + $minutesInSeconds);
      $timetableEntry->save();
    }
  }

}
