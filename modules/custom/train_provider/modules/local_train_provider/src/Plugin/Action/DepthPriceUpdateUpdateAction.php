<?php

namespace Drupal\local_train_provider\Plugin\Action;

use Drupal\master\Action\ViewsBulkOperationsActionBase;

/**
 * Update field 'depth_price_update'.
 *
 * @Action(
 *   id = "depth_price_update_update_action",
 *   label = @Translation("Update depth price update field"),
 *   type = "timetable_entry"
 * )
 */
class DepthPriceUpdateUpdateAction extends ViewsBulkOperationsActionBase {

  public function getEntityBundle() {
    return 'default';
  }

  public function getFieldName() {
    return 'depth_price_update';
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity) {
      /** @var \Drupal\local_train_provider\Entity\TimetableEntry $timetableEntry */
      $timetableEntry = $entity;
      $timetableEntry->setDepthForPriceUpdate($this->configuration['value'][0]['value'])->save();
    }
  }

}
