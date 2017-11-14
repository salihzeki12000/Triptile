<?php

namespace Drupal\payment\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Payment adapter plugin manager.
 */
class PaymentAdapterManager extends DefaultPluginManager {


  /**
   * Constructor for PaymentAdapterManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PaymentAdapter', $namespaces, $module_handler, 'Drupal\payment\Plugin\PaymentAdapter\PaymentAdapterInterface', 'Drupal\payment\Annotation\PaymentAdapter');

    $this->alterInfo('payment_adapter_info');
    $this->setCacheBackend($cache_backend, 'payment_adapter_plugins');
  }

  /**
   * Builds an array of payment adapters ready to use in 'select' and 'radios' elements.
   *
   * @return array
   */
  public function getPaymentAdapterOptions() {
    $options = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      $options[$plugin_id] = $definition['label'];
    }

    return $options;
  }

}
