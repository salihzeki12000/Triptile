<?php

namespace Drupal\payment\Plugin;

use Drupal\Core\Url;
use Drupal\plugin\PluginType\DefaultPluginTypeOperationsProvider;

/**
 * Class PaymentMethodOperationsProvider
 *
 * @package Drupal\payment\Plugin\PaymentMethod
 */
class PaymentMethodOperationsProvider extends DefaultPluginTypeOperationsProvider {

  public function getOperations($plugin_type_id) {
    $operations['list'] = [
      'title' => $this->t('View'),
      'url' => new Url('payment.payment_config.payment_methods'),
    ];

    return $operations;
  }

}
