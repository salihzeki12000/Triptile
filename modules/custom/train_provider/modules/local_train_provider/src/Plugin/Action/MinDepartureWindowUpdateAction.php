<?php

namespace Drupal\local_train_provider\Plugin\Action;

use Drupal\master\Action\ViewsBulkOperationsActionBase;

/**
 * Update minimal departure window.
 *
 * @Action(
 *   id = "min_departure_window_update_action",
 *   label = @Translation("Update minimal departure window"),
 *   type = "timetable_entry"
 * )
 */
class MinDepartureWindowUpdateAction extends ViewsBulkOperationsActionBase{

  public function getEntityBundle() {
    return 'default';
  }

  public function getFieldName() {
    return 'min_departure_window';
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity) {
      /** @var \Drupal\local_train_provider\Entity\TimetableEntry $timetableEntry */
      $timetableEntry = $entity;
      $timetableEntry->setMinDepartureWindow($this->configuration['value'][0]['value'])->save();
    }
  }

}
