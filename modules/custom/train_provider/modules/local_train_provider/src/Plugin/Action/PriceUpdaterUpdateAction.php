<?php

namespace Drupal\local_train_provider\Plugin\Action;

use Drupal\master\Action\ViewsBulkOperationsActionBase;

/**
 * Update field 'price_updater'.
 *
 * @Action(
 *   id = "price_updater_update_action",
 *   label = @Translation("Update price updater field"),
 *   type = "timetable_entry"
 * )
 */
class PriceUpdaterUpdateAction extends ViewsBulkOperationsActionBase {

  public function getEntityBundle() {
    return 'default';
  }

  public function getFieldName() {
    return 'price_updater';
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity) {
      /** @var \Drupal\local_train_provider\Entity\TimetableEntry $timetableEntry */
      $timetableEntry = $entity;
      $timetableEntry->setPriceUpdater($this->configuration['value'][0]['value'])->save();
    }
  }

}
