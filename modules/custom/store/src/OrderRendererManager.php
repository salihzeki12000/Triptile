<?php

namespace Drupal\store;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Manages Train Provider plugins.
 */
class OrderRendererManager extends DefaultPluginManager {

  /**
   * Creates the discovery object.
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
    $subdir = 'Plugin/OrderRenderer';
    $plugin_interface = 'Drupal\store\Plugin\OrderRenderer\OrderRendererInterface';
    $plugin_definition_annotation_name = 'Drupal\store\Annotation\OrderRenderer';
    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);
    $this->alterInfo('order_renderer_info');
    $this->setCacheBackend($cache_backend, 'order_renderer_info');
  }
}