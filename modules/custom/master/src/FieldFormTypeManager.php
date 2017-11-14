<?php

namespace Drupal\master;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Field form type plugin manager.
 */
class FieldFormTypeManager extends DefaultPluginManager {


  /**
   * Constructor for FieldFormTypeManager objects.
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
    parent::__construct('Plugin/FieldFormType', $namespaces, $module_handler, 'Drupal\master\FieldFormTypeInterface', 'Drupal\master\Annotation\FieldFormType');

    $this->alterInfo('master_field_form_type_info');
    $this->setCacheBackend($cache_backend, 'master_field_form_type_plugins');
  }

}
