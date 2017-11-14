<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\file\Entity\File;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;

/**
 * Provides entity destination plugin.
 *
 * @MigrateDestination(
 *   id = "entity:store_order"
 * )
 */
class StoreOrder extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  protected function save(ContentEntityInterface $entity, array $old_destination_id_values = []) {
    $ids = parent::save($entity, $old_destination_id_values);
    /** @var \Drupal\store\Entity\StoreOrder $order */
    $order = $entity;
    $pdfIds = [];
    // Skip saving pdf on the order without a reference.
    if ($order->getOrderNumber() != 'not set') {
      $files = file_scan_directory('public://migrated/order_pdf/' . $order->getOrderNumber(), '/.*/');
      foreach ($files as $file) {
        $pdf = File::Create([
          'uri' => $file->uri,
          'uid' => 1,
          'status' => 1,
        ]);
        $pdf->save();
        $pdfIds[] = $pdf->id();
      }
    }
    if ($pdfIds) {
      $order->pdf_file->setValue($pdfIds);
      $order->save();
    }

    return $ids;
  }
}