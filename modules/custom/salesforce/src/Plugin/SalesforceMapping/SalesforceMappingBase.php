<?php

namespace Drupal\salesforce\Plugin\SalesforceMapping;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\salesforce\Entity\SalesforceMappingObject;
use Drupal\salesforce\Entity\SalesforceMappingObjectInterface;
use Drupal\salesforce\SalesforceApi;
use Drupal\salesforce\SalesforceException;
use Drupal\salesforce\SalesforceSync;
use Drupal\salesforce\SelectQuery;

/**
 * Base class for Salesforce mapping plugins.
 */
abstract class SalesforceMappingBase extends PluginBase implements SalesforceMappingInterface {

  protected static $upsertField = false;

  /**
   * @var \Drupal\salesforce\SalesforceApi
   */
  protected $salesforceApi;

  /**
   * @var \Drupal\salesforce\SalesforceSync
   */
  protected $salesforceSync;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * @var \Drupal\salesforce\Entity\SalesforceMappingObject
   */
  protected $mappingObject;

  /**
   * @var \stdClass
   */
  protected $record;

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function setSalesforceApi(SalesforceApi $salesforce_api) {
    $this->salesforceApi = $salesforce_api;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSalesforceSync(SalesforceSync $salesforceSync) {
    $this->salesforceSync = $salesforceSync;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCacheBackend(CacheBackendInterface $cacheBackend) {
    $this->cacheBackend = $cacheBackend;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getImportFields() {
    $fields = ['Id'];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getSyncField() {
    return 'LastModifiedDate';
  }

  /**
   * {@inheritdoc}
   */
  public function setMappingObject(SalesforceMappingObjectInterface $mappingObject) {
    $this->mappingObject = $mappingObject;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecord() {
    if (!$this->record) {
      if (!$this->mappingObject) {
        throw new SalesforceException('Salesforce mapping object is not set.');
      }

      if ($id = $this->mappingObject->getRecordId()) {
        $this->record = $this->mappingObject->getData(SalesforceMappingObject::RECORD_KEY) ? : $this->salesforceApi->getRecord($id, $this->pluginDefinition['salesforce_object']);
      }
    }

    return $this->record;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    if (!$this->entity) {
      if (!$this->mappingObject) {
        throw new SalesforceException('Salesforce mapping object is not set.');
      }

      if ($this->mappingObject->getMappedEntityId()) {
        $this->entity = $this->entityTypeManager
          ->getStorage($this->pluginDefinition['entity_type_id'])
          ->load($this->mappingObject->getMappedEntityId());
      }
    }

    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function import() {
    if (!$this->mappingObject) {
      throw new SalesforceException('Salesforce mapping object is not set.');
    }

    $record = $this->getRecord();
    if (!$record) {
      throw new SalesforceException('Can\'t get record for import.');
    }
    /** @var \Drupal\salesforce\Entity\MappableEntityInterface $entity */
    $entity = $this->getEntity() ? : $this->createEntity();
    $entity->pullStart();
    $this->doImport($entity, $record);
    $entity->save();
    $entity->pullFinish();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function export() {
    if (!$this->mappingObject) {
      throw new SalesforceException('Salesforce mapping object is not set.');
    }

    $entity = $this->getEntity();
    if (!$entity) {
      throw new SalesforceException('Can\'t get entity for export.');
    }

    $record = new \stdClass();
    $this->doExport($entity, $record);

    $salesforceObject = $this->pluginDefinition['salesforce_object'];
    if (static::$upsertField && isset($record->{static::$upsertField})) {
      $value = $record->{static::$upsertField};
      unset($record->{static::$upsertField});
      $id = $this->salesforceApi->upsertRecord(static::$upsertField, $value, $salesforceObject, $record);
      if (!$id) {
        if ($this->mappingObject->getRecordId()) {
          $id = $this->mappingObject->getRecordId();
        }
        else {
          // Get record Id if record exists but is not mapped.
          $query = new SelectQuery($salesforceObject);
          $query->field('Id')
            ->condition(static::$upsertField, "'" . $value . "'");
          $records = $this->salesforceApi->query($query);
          $tmpRecord = reset($records);
          $id = $tmpRecord->Id;
        }
      }
    }
    elseif ($id = $this->mappingObject->getRecordId()) {
      $this->salesforceApi->updateRecord($id, $salesforceObject, $record);
    }
    else {
      $id = $this->salesforceApi->createRecord($salesforceObject, $record);
    }

    $record->Id = $id;
    if (!$record->Id) {
      throw new SalesforceException('Record hasn\'t been created.');
    }

    return $record;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if (!$this->mappingObject) {
      throw new SalesforceException('Salesforce mapping object is not set.');
    }

    if ($entity = $this->getEntity()) {
      $entity->delete();
    }
    if ($id = $this->mappingObject->getRecordId()) {
      $this->salesforceApi->deleteRecord($id, $this->pluginDefinition['salesforce_object']);
    }

    return true;
  }

  /**
   * Creates a new entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function createEntity() {
    return $this->entityTypeManager->getStorage($this->pluginDefinition['entity_type_id'])->create();
  }

  /**
   * Make sure the entity was pushed to Salesforce.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $plugin_id
   * @return \Drupal\salesforce\Entity\SalesforceMappingObject|null
   */
  protected function assureExport(EntityInterface $entity, $plugin_id) {
    $mappingObject = $this->getMappingObjectByEntity($entity, $plugin_id);
    if (!$mappingObject) {
      $mappingObject = $this->salesforceSync->setTriggerForEntity(SalesforceSync::SYNC_ACTION_PUSH, $entity->id(), $entity->getEntityTypeId(), $plugin_id);
    }
    if ($mappingObject && !$mappingObject->getRecordId()) {
      $this->salesforceSync->processSyncForMappingObject($mappingObject);
    }

    return $mappingObject;
  }

  /**
   * Make sure the record was pulled from Salesforce.
   *
   * @param string $id
   * @param string $plugin_id
   * @param \stdClass|null $record
   * @return \Drupal\salesforce\Entity\SalesforceMappingObject|null
   */
  protected function assureImport($id, $plugin_id, $record = null) {
    $mappingObject = $this->getMappingObjectByRecord($id, $plugin_id);
    if (!$mappingObject) {
      $mappingObject = $this->salesforceSync->setTriggerForRecord(SalesforceSync::SYNC_ACTION_PULL, $id, $plugin_id, $record);
    }
    if ($mappingObject && !$mappingObject->getMappedEntityId()) {
      $this->salesforceSync->processSyncForMappingObject($mappingObject);
    }

    return $mappingObject;
  }

  /**
   * Loads a mapping object for the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $plugin_id
   * @return \Drupal\salesforce\Entity\SalesforceMappingObject|null
   */
  protected function getMappingObjectByEntity(EntityInterface $entity, $plugin_id) {
    $mapping_objects = $this->entityTypeManager->getStorage('salesforce_mapping_object')->loadByProperties([
      'entity_id' => $entity->id(),
      'entity_type_id' => $entity->getEntityTypeId(),
      'plugin_id' => $plugin_id,
    ]);
    return reset($mapping_objects);
  }

  /**
   * Loads a mapping object for the record.
   *
   * @param string $id
   * @param string $plugin_id
   * @return \Drupal\salesforce\Entity\SalesforceMappingObject|null
   */
  protected function getMappingObjectByRecord($id, $plugin_id) {
    $mapping_objects = $this->entityTypeManager->getStorage('salesforce_mapping_object')->loadByProperties([
      'record_id' => $id,
      'mapping' => $plugin_id,
    ]);
    return reset($mapping_objects);
  }

  /**
   * Gets salesforce object metadata.
   *
   * @param string|null $object_type
   * @return \stdClass
   */
  protected function getSalesforceObjectMetadata($object_type = null) {
    $object_type = $object_type ? : $this->pluginDefinition['salesforce_object'];
    $metadata = $this->cacheBackend->get('salesforce_object_metadata_' . $object_type);
    if (!$metadata) {
      $metadata = $this->salesforceApi->describeObject($object_type);
      $this->cacheBackend->set('salesforce_object_metadata_' . $object_type, $metadata);
    }
    else {
      $metadata = $metadata->data;
    }

    return $metadata;
  }

  /**
   * Gets array of record types of current salesforce object keyed by their id.
   *
   * @param string|null $object_type
   * @return array
   */
  protected function getRecordTypes($object_type = null) {
    $metadata = $this->getSalesforceObjectMetadata($object_type);
    $recordTypes = [];
    if (isset($metadata->recordTypeInfos)) {
      foreach ($metadata->recordTypeInfos as $type) {
        $recordTypes[$type->recordTypeId] = $type->name;
      }
    }

    return $recordTypes;
  }

  /**
   * Imports data from record to the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \stdClass $record
   */
  abstract protected function doImport(EntityInterface $entity, \stdClass $record);

  /**
   * Exports data from the entity to salesforce record.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \stdClass $record
   */
  abstract protected function doExport(EntityInterface $entity, \stdClass $record);

}
