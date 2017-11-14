<?php

namespace Drupal\salesforce\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Salesforce mapping object entities.
 *
 * @ingroup salesforce
 */
interface SalesforceMappingObjectInterface extends  ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Salesforce mapping object name.
   *
   * @return string
   *   Name of the Salesforce mapping object.
   */
  public function getName();

  /**
   * Gets the Salesforce mapping object creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Salesforce mapping object.
   */
  public function getCreatedTime();

  /**
   * Sets the Salesforce mapping object creation timestamp.
   *
   * @param int $timestamp
   *   The Salesforce mapping object creation timestamp.
   *
   * @return \Drupal\salesforce\Entity\SalesforceMappingObjectInterface
   *   The called Salesforce mapping object entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets next scheduled sync operation.
   *
   * @return string
   */
  public function getNextSyncAction();

  /**
   * Sets the next sync action
   *
   * @param string $action
   * @return static
   */
  public function setNextSyncAction($action);

  /**
   * Sets a new mapping object status.
   *
   * @param string $status
   * @return static
   */
  public function setStatus($status);

  /**
   * Gets count of tries to sync the mapped objects.
   *
   * @return int
   */
  public function getTries();

  /**
   * Resets count of tries to sync the mapped objects.
   *
   * @return static
   */
  public function resetTries();

  /**
   * Sets count of tries to sync the mapped objects.
   *
   * @return static
   */
  public function setTries($count);

  /**
   * Gets the mapped salesforce record id.
   *
   * @return string
   */
  public function getRecordId();

  /**
   * Sets the mapped salesforce record id.
   *
   * @param string $record_id
   * @return static
   */
  public function setRecordId($record_id);

  /**
   * Gets the salesforce object type of mapped record.
   *
   * @return string
   */
  public function getSalesforceObject();

  /**
   * Sets the mapped salesforce record object type.
   *
   * @param string $salesforce_object
   * @return static
   */
  public function setSalesforceObject($salesforce_object);

  /**
   * Gets the mapped entity id.
   *
   * @return string
   */
  public function getMappedEntityId();

  /**
   * Sets the mapped entity id.
   *
   * @param string|int $entity_id
   * @return static
   */
  public function setMappedEntityId($entity_id);

  /**
   * Gets the mapped entity type id.
   *
   * @return string
   */
  public function getMappedEntityTypeId();

  /**
   * Sets the mapped entity type id.
   *
   * @param string $entity_type_id
   * @return static
   */
  public function setMappedEntityTypeId($entity_type_id);

  /**
   * Sets the last sync action.
   *
   * @param string $action
   * @return static
   */
  public function setLastSyncAction($action);

  /**
   * Gets the last sync action.
   *
   * @return string
   */
  public function getLastSyncAction();

  /**
   * Sets the timestamp when the objects was lastly synced.
   *
   * @param int $time
   * @return static
   */
  public function setLastSyncTime($time);

  /**
   * Gets the timestamp of last sync.
   *
   * @return int
   */
  public function getLastSyncTime();

  /**
   * Notifies the mapping object that sync started.
   *
   * @return static
   */
  public function syncStart();

  /**
   * Notifies the mapping object that sync finished.
   *
   * @return static
   */
  public function syncFinish();

  /**
   * Checks if sync is processing.
   *
   * @return bool
   */
  public function isSyncProcessing();

  /**
   * Gets id of mapping plugin that has to be used to process the mapping.
   *
   * @return string
   */
  public function getMapping();

  /**
   * Sets id of mapping plugin that has to be used to process the mapping.
   *
   * @param $mapping
   * @return static
   */
  public function setMapping($mapping);

}
