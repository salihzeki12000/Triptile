<?php

namespace Drupal\bene_train_provider\Plugin\TrainProvider;

use Drupal\Core\Url;
use Drupal\train_provider\Plugin\TrainProvider\PluginOperationsProvider as PluginOperationsProviderBase;

class PluginOperationsProvider extends PluginOperationsProviderBase {

  public function getOperations($plugin_id) {
    $operations['get_data'] = [
      'title' => $this->t('Get route data'),
      'url' => Url::fromRoute('train_provider.route_data.train_provider', ['train_provider' => $plugin_id])
    ];
    $operations['view_log'] = [
      'title' => $this->t('View log files'),
      'url' => new Url('bene_train_provider.log'),
    ];
    return array_merge(parent::getOperations($plugin_id), $operations);
  }

}
