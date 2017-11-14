<?php

namespace Drupal\payment;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Render\Element;

class TransactionViewBuilder extends EntityViewBuilder {

  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);

    if ($view_mode == 'full') {
      /** @var \Drupal\payment\Entity\Transaction $transaction */
      foreach ($entities as $entity_key => $transaction) {
        $log = $transaction->getLog();
        $output = '<pre>' . print_r($log, true) . '</pre>';
        $build[$entity_key]['log'] = [
          '#markup' => $output,
          '#weight' => 100,
        ];
      }
    }
  }

}
