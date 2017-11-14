<?php

namespace Drupal\local_train_provider\Plugin\Action;

use Drupal\master\Action\ViewsBulkOperationsActionBase;

/**
 * Update field 'status'.
 *
 * @Action(
 *   id = "status_update_action",
 *   label = @Translation("Update status"),
 *   type = "timetable_entry"
 * )
 */
class StatusUpdateAction extends ViewsBulkOperationsActionBase {

  public function getEntityBundle() {
    return 'default';
  }

  public function getFieldName() {
    return 'status';
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity) {
      /** @var \Drupal\local_train_provider\Entity\TimetableEntry $timetableEntry */
      $timetableEntry = $entity;
      $timetableEntry->setStatus($this->configuration['value']['value'])->save();
    }
  }

}
