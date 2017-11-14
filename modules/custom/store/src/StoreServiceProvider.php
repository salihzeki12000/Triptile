<?php

namespace Drupal\store;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
* Modifies the language manager service.
*/
class StoreServiceProvider extends ServiceProviderBase {

  /**
  * {@inheritdoc}
  */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('plugin.manager.currency.amount_formatter');
    $definition->setClass('Drupal\store\AmountFormatterManager');
  }

}