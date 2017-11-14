<?php
namespace Drupal\train_provider\Plugin;

use Drupal\Core\Url;
use Drupal\plugin\PluginType\DefaultPluginTypeOperationsProvider;

/**
 * Class TrainProviderOperationsProvider
 *
 * @package Drupal\train_provider\Plugin
 */
class TrainProviderOperationsProvider extends DefaultPluginTypeOperationsProvider{

  /**
   * {@inheritdoc}
   */
  public function getOperations($plugin_type_id) {
    $operations['list'] = [
      'title' => $this->t('View'),
      'url' => new Url('train_provider.configuration_page'),
    ];

    return $operations;
  }

}
