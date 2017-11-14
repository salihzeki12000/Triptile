<?php

namespace Drupal\trip_base\Plugin\SalesforceMapping;

use Drupal\Core\Entity\EntityInterface;
use Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase;
use Drupal\salesforce\SalesforceException;
use Drupal\salesforce\SelectQuery;
use Drupal\trip_base\Entity\Transfer;

/**
 * Class TransferSalesforceMapping
 *
 * @SalesforceMapping(
 *   id = "transfer_transfer",
 *   label = @Translation("Mapping of Transfer to Standard_service__c"),
 *   entity_type_id = "transfer",
 *   salesforce_object = "Standard_service__c",
 *   entity_operations = {},
 *   object_operations = {"update", "delete"},
 *   priority = "salesforce"
 * )
 */
class TransferSalesforceMapping extends SalesforceMappingBase {

  const TRANSFER_RECORD_TYPE = 'Transfer';

  /**
   * {@inheritdoc}
   */
  public function getImportFields() {
    $fields = [
      'Name',
      'Hub_transfer__c',
      'Preferred__c',
      'Triptile_publish__c',
    ];

    return array_merge(parent::getImportFields(), $fields);
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\trip_base\Entity\Transfer $transfer
   */
  protected function doImport(EntityInterface $transfer, \stdClass $record) {

    if ($record->Hub_transfer__c) {
      $hubMappingObject = $this->assureImport($record->Hub_transfer__c, 'hub_hub');
      if (!$hubMappingObject || !$hubMappingObject->getMappedEntityId()) {
        throw new SalesforceException('Hub has not been imported yet');
      }
      $transfer->setHub($hubMappingObject->getMappedEntityId());
    }

    $transfer->setName($record->Name);
    $transfer->setPreferred((bool) $record->Preferred__c);
    $transfer->setPublished((bool) $record->Triptile_publish__c);
    $this->importPriceOptions($transfer, $record);
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

    $id = array_search(static::TRANSFER_RECORD_TYPE, $this->getRecordTypes());
    $conditions[] = [
      'field' => 'RecordTypeId',
      'value' => "'" . $id . "'",
      'operator' => '=',
    ];

    $conditions[] = [
      'field' => 'Hub_transfer__r.Id',
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
   * Imports price options for the transfers.
   *
   * @param \Drupal\trip_base\Entity\Transfer $transfer
   * @param \stdClass $record
   */
  protected function importPriceOptions(Transfer $transfer, \stdClass $record) {
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

      $transfer->setPriceOptionsIds($ids);
    }
  }

}
