<?php

namespace Drupal\salesforce\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Salesforce mapping plugin manager.
 */
class SalesforceMappingManager extends DefaultPluginManager {

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructor for SalesforceMappingManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ContainerInterface $container) {
    parent::__construct('Plugin/SalesforceMapping', $namespaces, $module_handler, 'Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingInterface', 'Drupal\salesforce\Annotation\SalesforceMapping');

    $this->alterInfo('salesforce_mapping_info');
    $this->setCacheBackend($cache_backend, 'salesforce_salesforce_mapping_plugins');
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   * @return \Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    /** @var \Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase $plugin */
    $plugin = parent::createInstance($plugin_id, $configuration);
    $plugin->setSalesforceApi($this->container->get('salesforce_api'))
      ->setSalesforceSync($this->container->get('salesforce_sync'))
      ->setEntityTypeManager($this->container->get('entity_type.manager'))
      ->setCacheBackend($this->container->get('cache.default'));
    return $plugin;
  }

  /**
   * Gets plugin ids by entity type.
   *
   * @param string $entity_type_id
   * @return string[]
   */
  public function findDefinitionsForEntityType($entity_type_id) {
    $definitions = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if ($definition['entity_type_id'] == $entity_type_id) {
        $definitions[$plugin_id] = $definition;
      }
    }

    return $definitions;
  }

  /**
   * Gets plugin ids by salesforce object.
   *
   * @param string $salesforce_object
   * @return string[]
   */
  public function findDefinitionsForSalesforceObject($salesforce_object) {
    $definitions = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if ($definition['salesforce_object'] == $salesforce_object) {
        $definitions[$plugin_id] = $definition;
      }
    }

    return $definitions;
  }

}
