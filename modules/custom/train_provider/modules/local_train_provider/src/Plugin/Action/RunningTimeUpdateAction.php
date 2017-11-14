<?php

namespace Drupal\local_train_provider\Plugin\Action;

use Drupal\master\Action\ViewsBulkOperationsActionBase;

/**
 * Update field 'running_time'.
 *
 * @Action(
 *   id = "running_time_update_action",
 *   label = @Translation("Update running time"),
 *   type = "timetable_entry"
 * )
 */
class RunningTimeUpdateAction extends ViewsBulkOperationsActionBase{

  public function getEntityBundle() {
    return 'default';
  }

  public function getFieldName() {
    return 'running_time';
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity) {
      /** @var \Drupal\local_train_provider\Entity\TimetableEntry $timetableEntry */
      $timetableEntry = $entity;
      $daysInSeconds = 24 * 3600 * $this->configuration['value'][0]['running_time']['days'];
      $hoursInSeconds = 3600 * $this->configuration['value'][0]['running_time']['hours'];
      $minutesInSeconds = 60 * $this->configuration['value'][0]['running_time']['minutes'];
      $timetableEntry->set('running_time', $daysInSeconds + $hoursInSeconds + $minutesInSeconds);
      $timetableEntry->save();
    }
  }

}
