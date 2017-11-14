<?php

namespace Drupal\trip_base\Plugin\SalesforceMapping;

use Drupal\Core\Entity\EntityInterface;
use Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase;
use Drupal\salesforce\SalesforceException;
use Drupal\salesforce\SelectQuery;
use Drupal\trip_base\Entity\Activity;

/**
 * Class TransferSalesforceMapping
 *
 * @SalesforceMapping(
 *   id = "activity_activity",
 *   label = @Translation("Mapping of Activity to Standard_service__c"),
 *   entity_type_id = "activity",
 *   salesforce_object = "Standard_service__c",
 *   entity_operations = {},
 *   object_operations = {"update", "delete"},
 *   priority = "salesforce"
 * )
 */
class ActivitySalesforceMapping extends SalesforceMappingBase {

  const ACTIVITY_RECORD_TYPE = 'Activity';

  /**
   * {@inheritdoc}
   */
  public function getImportFields() {
    $fields = [
      'Name',
      'Hub__c',
      'Preferred__c',
      'Triptile_publish__c',
    ];

    return array_merge(parent::getImportFields(), $fields);
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\trip_base\Entity\Activity $activity
   */
  protected function doImport(EntityInterface $activity, \stdClass $record) {

    if ($record->Hub__c) {
      $hubMappingObject = $this->assureImport($record->Hub__c, 'hub_hub');
      if (!$hubMappingObject || !$hubMappingObject->getMappedEntityId()) {
        throw new SalesforceException('Hub has not been imported yet');
      }
      $activity->setHub($hubMappingObject->getMappedEntityId());
    }

    $activity->setName($record->Name);
    $activity->setPreferred((bool) $record->Preferred__c);
    $activity->setPublished((bool) $record->Triptile_publish__c);
    $this->importPriceOptions($activity, $record);
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\trip_base\Entity\Hub $entity
   */
  protected function doExport(EntityInterface $entity, \stdClass $record) {
    // Do nothing
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryConditions() {
    $conditions = [];

    $id = array_search(static::ACTIVITY_RECORD_TYPE, $this->getRecordTypes());
    $conditions[] = [
      'field' => 'RecordTypeId',
      'value' => "'" . $id . "'",
      'operator' => '=',
    ];

    $conditions[] = [
      'field' => 'Hub__r.Id',
      'value' => "''",
      'operator' => '!=',
    ];

    return $conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function isSyncAllowed($action) {
    return true;
  }

  /**
   * Imports price options for the activities.
   *
   * @param \Drupal\trip_base\Entity\Activity $activity
   * @param \stdClass $record
   */
  protected function importPriceOptions(Activity $activity, \stdClass $record) {
    if (!empty($record->Id)) {
      /** @var \Drupal\salesforce\Plugin\SalesforceMappingManager $salesforceMappingManager */
      $salesforceMappingManager = \Drupal::service('plugin.manager.salesforce_mapping');
      /** @var \Drupal\store\Plugin\SalesforceMapping\BaseProductSalesforceMapping $mappingPlugin */
      $mappingPlugin = $salesforceMappingManager->createInstance('base_product_price');

      $query = new SelectQuery('Price__c');
      $query->condition('Standard_Service__r.Id', "'" . $record->Id . "'")
        ->field($mappingPlugin->getImportFields());
      $prices = $this->salesforceApi->query($query);
      $ids = [];
      foreach ($prices as $price) {
        $mappingObject = $this->assureImport($price->Id, 'base_product_price', $price);
        $ids[] = $mappingObject->getMappedEntityId();
      }

      $activity->setPriceOptionsIds($ids);
    }
  }

}
