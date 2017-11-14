<?php

namespace Drupal\salesforce;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\State\StateInterface;
use Drupal\salesforce\Entity\MappableEntityInterface;
use Drupal\salesforce\Entity\SalesforceMappingObject;
use Drupal\salesforce\Plugin\SalesforceMappingManager;

class SalesforceSync {

  const
    OPERATION_UPDATE = 'update',
    OPERATION_DELETE = 'delete';

  const
    SYNC_ACTION_PUSH = 'push',
    SYNC_ACTION_PULL = 'pull',
    SYNC_ACTION_DELETE = 'delete';

  const
    SYNC_STATUS_SUCCESS = 'success',
    SYNC_STATUS_ERROR = 'error',
    SYNC_STATUS_CONFLICT = 'conflict';

  const MAX_SYNC_TRIES = 10;

  const MAX_SYNC_PER_TIME = 100;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $salesforceMappingObjectStorage;

  /**
   * @var \Drupal\salesforce\SalesforceApi
   */
  protected $salesforceApi;

  /**
   * @var \Drupal\salesforce\Plugin\SalesforceMappingManager
   */
  protected $mappingManager;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $accountProxy;

  /**
   * SalesforceSync constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   * @param \Drupal\salesforce\SalesforceApi $salesforce_api
   * @param \Drupal\salesforce\Plugin\SalesforceMappingManager $mapping_manager
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Drupal\Core\Session\AccountProxy $account_proxy
   */
  public function __construct(LoggerChannelFactory $logger_factory, EntityTypeManager $entity_type_manager, QueryFactory $entity_query, SalesforceApi $salesforce_api, SalesforceMappingManager $mapping_manager, StateInterface $state, AccountProxy $account_proxy) {
    $this->logger = $logger_factory->get('salesforce');
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entity_query;
    $this->salesforceMappingObjectStorage = $this->entityTypeManager->getStorage('salesforce_mapping_object');
    $this->salesforceApi = $salesforce_api;
    $this->mappingManager = $mapping_manager;
    $this->state = $state;
    $this->accountProxy = $account_proxy;
  }

  /**
   * Sets a sync trigger on the mapping object.
   *
   * @param string $action
   * @param \Drupal\salesforce\Entity\SalesforceMappingObject $mapping_object
   * @return bool
   * @throws \Drupal\salesforce\SalesforceException
   */
  public function setTrigger($action, SalesforceMappingObject $mapping_object) {
    $result = false;
    $mapping = $this->mappingManager->createInstance($mapping_object->getMapping());
    $mapping->setMappingObject($mapping_object);
    $priority = $mapping->getPluginDefinition()['priority'];

    if (!$mapping_object->isSyncProcessing() && $mapping->isSyncAllowed($action)) {
      if ($action == static::SYNC_ACTION_PULL && $mapping_object->getNextSyncAction() == static::SYNC_ACTION_PUSH && $priority != 'salesforce') {
        // @todo Notify conflict

        if ($this->accountProxy->hasPermission('see salesforce conflict error')) {
          drupal_set_message(t('Trying to schedule PULL while PUSH is scheduled.'), 'error');
        }

        $mapping_object->setStatus(static::SYNC_STATUS_CONFLICT);
        $this->logger->critical('Trying to schedule PULL while PUSH is scheduled.', [
          'link' => $mapping_object->toLink('view')
            ->toString()
        ]);
      }
      elseif ($action == static::SYNC_ACTION_PUSH && $mapping_object->getNextSyncAction() == static::SYNC_ACTION_PULL && $priority != 'drupal') {
        // @todo Notify conflict

        if ($this->accountProxy->hasPermission('see salesforce conflict error')) {
          drupal_set_message(t('Trying to schedule PUSH while PULL is scheduled.'), 'error');
        }

        $mapping_object->setStatus(static::SYNC_STATUS_CONFLICT);
        $this->logger->critical('Trying to schedule PUSH while PULL is scheduled.', [
          'link' => $mapping_object->toLink('view')
            ->toString()
        ]);
      }
      // Avoid creation of new mapping objects on deletion.
      elseif ($action == static::SYNC_ACTION_DELETE && $mapping_object->isNew()) {
        return $result;
      }
      else {
        // 'delete' can override any other operation. It's not a conflict
        // @todo Should it be considered as a conflict?

        $mapping_object->resetTries()
          ->setNextSyncAction($action);
      }

      $mapping_object->save();
      $result = TRUE;
    }

    return $result;
  }

  /**
   * Sets a sync trigger for the salesforce id.
   *
   * @param string $action
   * @param string $record_id
   * @param string $plugin_id
   * @param \stdClass $record
   * @return bool|\Drupal\salesforce\Entity\SalesforceMappingObject
   */
  public function setTriggerForRecord($action, $record_id, $plugin_id, $record = null) {
    $record_id = $this->salesforceApi->convertId($record_id);
    $properties = [
      'record_id' => $record_id,
      'mapping' => $plugin_id,
    ];
    $mapping_objects = $this->salesforceMappingObjectStorage->loadByProperties($properties);
    if (!$mapping_object = reset($mapping_objects)) {
      $mapping_object = SalesforceMappingObject::create()
        ->setRecordId($record_id)
        ->setSalesforceObject($this->mappingManager->getDefinition($plugin_id)['salesforce_object'])
        ->setMapping($plugin_id);
    }
    if ($record) {
      $mapping_object->setData(SalesforceMappingObject::RECORD_KEY, $record);
    }
    return $this->setTrigger($action, $mapping_object) ? $mapping_object : null;
  }

  /**
   * Sets a sync trigger for the entity.
   *
   * @param string $action
   * @param string|int $entity_id
   * @param string $entity_type_id
   * @param string $plugin_id
   * @return bool|\Drupal\salesforce\Entity\SalesforceMappingObject
   */
  public function setTriggerForEntity($action, $entity_id, $entity_type_id, $plugin_id) {
    $properties = [
      'entity_id' => $entity_id,
      'entity_type_id' => $entity_type_id,
      'mapping' => $plugin_id,
    ];
    $mapping_objects = $this->salesforceMappingObjectStorage->loadByProperties($properties);
    if (!$mapping_object = reset($mapping_objects)) {
      $mapping_object = SalesforceMappingObject::create()
        ->setMappedEntityId($entity_id)
        ->setMappedEntityTypeId($entity_type_id)
        ->setMapping($plugin_id);
    }
    return $this->setTrigger($action, $mapping_object) ? $mapping_object : null;
  }

  /**
   * Reacts on a CRUD operation on an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $operation
   */
  public function entityCrud(EntityInterface $entity, $operation) {
    if ($entity instanceof MappableEntityInterface && !$entity->isPullProcessing()) {
      foreach ($this->mappingManager->findDefinitionsForEntityType($entity->getEntityTypeId()) as $pluginId => $definition) {
        if (in_array($operation, $definition['entity_operations'])) {
          switch ($operation) {
            case static::OPERATION_UPDATE:
              $this->setTriggerForEntity(static::SYNC_ACTION_PUSH, $entity->id(), $entity->getEntityTypeId(), $pluginId);
              break;
            case static::OPERATION_DELETE:
              $this->setTriggerForEntity(static::SYNC_ACTION_DELETE, $entity->id(), $entity->getEntityTypeId(), $pluginId);
              break;
          }
        }
      }
    }
  }

  /**
   * Sets sync triggers on mapping objects for all created, changed and deleted
   * records in Salesforce.
   *
   * @param string|null $salesforce_object
   */
  public function triggerRecordsSync($salesforce_object = null) {
    foreach ([static::OPERATION_UPDATE, static::OPERATION_DELETE] as $operation) {
      $this->doTriggerRecordsSync($operation, $salesforce_object);
    }
  }

  /**
   * Sets sync triggers on mapping objects for changed records in Salesforce.
   *
   * @param string|null $salesforce_object
   */
  public function triggerUpdatedRecordsSync($salesforce_object = null) {
    $this->doTriggerRecordsSync(static::OPERATION_UPDATE, $salesforce_object);
  }

  /**
   * Sets sync triggers on mapping objects for deleted records in Salesforce.
   *
   * @param string|null $salesforce_object
   */
  public function triggerDeletedRecordsSync($salesforce_object = null) {
    $this->doTriggerRecordsSync(static::OPERATION_DELETE, $salesforce_object);
  }

  /**
   * Sets trigger for an operation on mapping object using data from Salesforce.
   *
   * @param string $operation
   * @param string|null $salesforce_object
   */
  protected function doTriggerRecordsSync($operation, $salesforce_object = null) {
    $definitions = $salesforce_object ? $this->mappingManager->findDefinitionsForSalesforceObject($salesforce_object) : $this->mappingManager->getDefinitions();

    foreach ($definitions as $plugin_id => $definition) {
      if (in_array($operation, $definition['object_operations'])) {
        $mapping = $this->mappingManager->createInstance($plugin_id);
        $state_key = 'salesforce_sync_' . $operation . '_' . $definition['salesforce_object'] . '_' . $plugin_id;
        $records = [];
        $action = null;

        switch ($operation) {
          case static::OPERATION_UPDATE:
            $query = new SelectQuery($definition['salesforce_object']);
            $query->field($mapping->getImportFields());

            $condition_value = gmdate('Y-m-d\TH:i:s\Z', $this->state->get($state_key, REQUEST_TIME - 1200));
            $query->condition($mapping->getSyncField(), $condition_value, '>');
            foreach ($mapping->getQueryConditions() as $condition) {
              is_array($condition) ? $query->condition($condition['field'], $condition['value'], $condition['operator']) : $query->where($condition);
            }
            $records = $this->salesforceApi->query($query);
            $action = static::SYNC_ACTION_PULL;
            break;

          case static::OPERATION_DELETE:
            $start_date = new DrupalDateTime();
            $start_date_timestamp = max($this->state->get($state_key, REQUEST_TIME - 1200), REQUEST_TIME - 30 * 24 * 60 * 60 + 1000);
            $start_date->setTimestamp($start_date_timestamp);
            $end_date = new DrupalDateTime();
            $end_date->setTimestamp(REQUEST_TIME);
            $records = $this->salesforceApi->getDeleted($definition['salesforce_object'], $start_date, $end_date);
            $action = static::SYNC_ACTION_DELETE;
            break;
        }

        $this->state->set($state_key, time());

        foreach ($records as $record) {
          if ($record->Id) {
            $this->setTriggerForRecord($action, $record->Id, $plugin_id, $record);
          }
        }
      }
    }
  }

  /**
   * Processes sync on all triggered mapping objects.
   *
   * @return int
   *  Count of processed objects.
   */
  public function processSync() {
    $count = 0;
    if (!$this->isLocked()) {
      $this->lock();

      $ids = $this->entityQuery->get('salesforce_mapping_object')
        ->condition('tries', static::MAX_SYNC_TRIES, '<')
        ->exists('next_action')
        ->sort('changed')
        ->range(0, static::MAX_SYNC_PER_TIME)
        ->execute();
      $mapping_objects = $this->entityTypeManager->getStorage('salesforce_mapping_object')
        ->loadMultiple($ids);
      /** @var \Drupal\salesforce\Entity\SalesforceMappingObject $mapping_object */
      foreach ($mapping_objects as $mapping_object) {
        $this->processSyncForMappingObject($mapping_object);
        $count++;
      }

      $this->unlock();
    }

    return $count;
  }

  /**
   * Processes sync on a mapping object.
   *
   * @param \Drupal\salesforce\Entity\SalesforceMappingObject $mapping_object
   * @throws \Drupal\salesforce\SalesforceException
   */
  public function processSyncForMappingObject(SalesforceMappingObject $mapping_object) {
    if ($mapping_object->getTries() < static::MAX_SYNC_TRIES && $mapping_object->getNextSyncAction()) {
      $mapping_object->syncStart();
      $mapping = $this->mappingManager->createInstance($mapping_object->getMapping());
      $mapping->setMappingObject($mapping_object);
      $mapping_object->setTries($mapping_object->getTries() + 1);
      $deleted = false;
      $success = false;

      try {
        switch ($mapping_object->getNextSyncAction()) {
          case static::SYNC_ACTION_PULL:
            if ($entity = $mapping->import()) {
              if (!$mapping_object->getMappedEntityId()) {
                $mapping_object->setMappedEntityId($entity->id())
                  ->setMappedEntityTypeId($entity->getEntityTypeId());
              }
              $record = $mapping->getRecord();
              // @todo improve log.
              $this->logger->info('Record ' . $record->Id . ' imported as entity ' . $entity->label() . '.', ['link' => $mapping_object->toLink('view')->toString()]);
              $success = true;
            }
            break;

          case static::SYNC_ACTION_PUSH:
            if ($record = $mapping->export()) {
              if (!$mapping_object->getRecordId()) {
                $mapping_object->setRecordId($record->Id)
                  ->setSalesforceObject($mapping->getPluginDefinition()['salesforce_object']);
              }
              $entity = $mapping->getEntity();
              // @todo improve log.
              $this->logger->info('Entity ' . $entity->label() . ' exported as record ' . $mapping_object->getRecordId() . '.', ['link' => $mapping_object->toLink('view')->toString()]);
              $success = true;
            }
            break;

          case static::SYNC_ACTION_DELETE:
            if ($mapping->delete()) {
              // @todo improve log
              $this->logger->info('Entity ' . $mapping_object->getMappedEntityId()
                . ' of type ' . $mapping_object->getMappedEntityTypeId() . ' and record '
                . $mapping_object->getRecordId() . ' deleted.');
              $mapping_object->delete();
              $deleted = true;
              $success = true;
            }

            break;
        }
      }
      catch (\Exception $exception) {
        watchdog_exception('salesforce', $exception);
      }

      if (!$deleted) {
        if ($success) {
          $mapping_object->setLastSyncAction($mapping_object->getNextSyncAction())
            ->setLastSyncTime(REQUEST_TIME)
            ->setNextSyncAction(null);
        }
        $mapping_object->save();
      }

      $mapping_object->syncFinish();
    }
  }

  /**
   * Checks if a parallel sync process is run.
   *
   * @return bool
   */
  protected function isLocked() {
    $locked = $this->state->get('salesforce_sync_locked', false);
    $expire = $this->state->get('salesforce_sync_lock_expire', 0);
    return $locked || $expire < (microtime() - 60 * 10);
  }

  /**
   * Sets lock to avoid from parallel sync execution.
   */
  protected function lock() {
    $this->state->set('salesforce_sync_locked', true);
    $this->state->set('salesforce_sync_lock_expire', microtime());
  }

  /**
   * Unlocks sync.
   */
  protected function unlock() {
    $this->state->set('salesforce_sync_locked', false);
    $this->state->set('salesforce_sync_lock_expire', 0);
  }

}
