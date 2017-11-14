<?php

namespace Drupal\payment\Plugin;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Payment method plugin manager.
 */
class PaymentMethodManager extends DefaultPluginManager {

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructor for PaymentMethodManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactory $config_factory) {
    parent::__construct('Plugin/PaymentMethod', $namespaces, $module_handler, 'Drupal\payment\Plugin\PaymentMethod\PaymentMethodInterface', 'Drupal\payment\Annotation\PaymentMethod');

    $this->alterInfo('payment_method_info');
    $this->setCacheBackend($cache_backend, 'payment_method_plugins');

    $this->configFactory = $config_factory;
  }

  /**
   * Builds an array of payment methods ready to use in 'select' and 'radios' elements.
   *
   * @param bool $enabled_only
   * @return array
   */
  public function getPaymentMethodOptions($enabled_only = false) {
    $ordered_plugins = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      $config = $this->configFactory->get('plugin.plugin_configuration.payment_method.' . $plugin_id);
      if (!$enabled_only || $config->get('status')) {
        $ordered_plugins[$plugin_id] = [
          'weight' => $config->get('weight'),
          'label' => $definition['label'],
        ];
      }
    }
    uasort($ordered_plugins, [SortArray::class, 'sortByWeightElement']);

    $options = [];
    foreach ($ordered_plugins as $plugin_id => $value) {
      $options[$plugin_id] = $value['label'];
    }

    return $options;
  }

}
