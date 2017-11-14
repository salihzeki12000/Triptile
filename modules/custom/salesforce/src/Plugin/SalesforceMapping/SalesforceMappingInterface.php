<?php

namespace Drupal\salesforce\Plugin\SalesforceMapping;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\salesforce\Entity\SalesforceMappingObject;
use Drupal\salesforce\Entity\SalesforceMappingObjectInterface;
use Drupal\salesforce\SalesforceApi;
use Drupal\salesforce\SalesforceSync;

/**
 * Defines an interface for Salesforce mapping plugins.
 */
interface SalesforceMappingInterface extends PluginInspectionInterface {

  /**
   * Sets the salasefoce API service object.
   *
   * @param \Drupal\salesforce\SalesforceApi $salesforce_api
   * @return static
   */
  public function setSalesforceApi(SalesforceApi $salesforce_api);

  /**
   * Sets the entity type manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @return static
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager);

  /**
   * Sets salesforce sync service.
   *
   * @param \Drupal\salesforce\SalesforceSync $salesforceSync
   * @return static
   */
  public function setSalesforceSync(SalesforceSync $salesforceSync);

  /**
   * Sets cache service.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   * @return static
   */
  public function setCacheBackend(CacheBackendInterface $cacheBackend);

  /**
   * Gets the list of salesforce object fields that has to be imported.
   *
   * @return string[]
   */
  public function getImportFields();

  /**
   * Gets conditions that has to be applier to query when import records.
   *
   * @return array
   */
  public function getQueryConditions();

  /**
   * Gets the datetime field name that used to load new records that have not been synced yet.
   *
   * @return static
   */
  public function getSyncField();

  /**
   * Sets a mapping object that will be used to process mapping.
   *
   * @param \Drupal\salesforce\Entity\SalesforceMappingObjectInterface $mappingObject
   * @return static
   */
  public function setMappingObject(SalesforceMappingObjectInterface $mappingObject);

  /**
   * Gets the mapped record object.
   *
   * @return \stdClass
   */
  public function getRecord();

  /**
   * Gets the mapped entity object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity();

  /**
   * Processes import of a record from Salesforce.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function import();

  /**
   * Processes export of an entity to Salesforce.
   *
   * @return \stdClass|null
   */
  public function export();

  /**
   * Processes deletion of mapped objects.
   *
   * @return bool
   */
  public function delete();

  /**
   * Determines if sync of mapped objects is allowed.
   *
   * @param string $action
   * @return bool
   */
  public function isSyncAllowed($action);

}
