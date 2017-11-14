<?php

namespace Drupal\store\Plugin\SalesforceMapping;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase;
use Drupal\salesforce\SalesforceException;
use Drupal\store\Entity\BaseProduct;

/**
 * Class BaseProductSalesforceMapping
 *
 * @SalesforceMapping(
 *   id = "base_product_price",
 *   label = @Translation("Mapping of Base product to Price__c"),
 *   entity_type_id = "base_product",
 *   salesforce_object = "Price__c",
 *   entity_operations = {},
 *   object_operations = {"update", "delete"},
 *   priority = "salesforce"
 * )
 */
class BaseProductSalesforceMapping extends SalesforceMappingBase {

  const
    STANDARD_SERVICE_ACCOMMODATION_RECORD_TYPE = 'Accommodation',
    STANDARD_SERVICE_ACTIVITY_RECORD_TYPE = 'Activity',
    STANDARD_SERVICE_TRANSFER_RECORD_TYPE = 'Transfer',
    STANDARD_SERVICE_TRANSPORT_RECORD_TYPE = 'Transport';

  /**
   * {@inheritdoc}
   */
  public function getImportFields() {
    $fields = [
      'Name',
      'Sales_price_USD__c',
      'Start_Date__c',
      'End_Date__c',
      'Standard_Service__r.Name',
      'Standard_Service__r.Preferred__c',
      'Standard_Service__r.Max_PAX__c',
      'Standard_Service__r.Triptile_publish__c',
    ];

    return array_merge(parent::getImportFields(), $fields);
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\store\Entity\BaseProduct $product
   */
  protected function doImport(EntityInterface $product, \stdClass $record) {
    $price = \Drupal::service('store.price')->get($record->Sales_price_USD__c, 'USD');
    $product
      ->setName(substr($record->Standard_Service__r->Name, 0, 49))
      ->setPrice($price)
      ->setAvailableFrom(new DrupalDateTime($record->Start_Date__c))
      ->setAvailableUntil(new DrupalDateTime($record->End_Date__c));
    $product->setPreferred((bool) $record->Standard_Service__r->Preferred__c);
    $product->setPublished((bool) $record->Standard_Service__r->Triptile_publish__c);
    if ($record->Standard_Service__r->Max_PAX__c) {
      $product->setMaxQuantity($record->Standard_Service__r->Max_PAX__c);
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function doExport(EntityInterface $entity, \stdClass $record) {
    // Do nothing here
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryConditions() {
    $conditions = [];

    $recordTypeNames = [
      static::STANDARD_SERVICE_ACCOMMODATION_RECORD_TYPE,
      static::STANDARD_SERVICE_ACTIVITY_RECORD_TYPE,
      static::STANDARD_SERVICE_TRANSFER_RECORD_TYPE,
      static::STANDARD_SERVICE_TRANSPORT_RECORD_TYPE,
    ];
    $recordTypes = $this->getRecordTypes('Standard_service__c');
    $ids = [];
    foreach ($recordTypeNames as $recordTypeName) {
      if ($id = array_search($recordTypeName, $recordTypes)) {
        $ids[] = "'" . $id . "'";
      }
    }
    $conditions[] = [
      'field' => 'Standard_Service__r.RecordTypeId',
      'value' => $ids,
      'operator' => 'IN',
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
   * {@inheritdoc}
   */
  protected function createEntity() {
    return BaseProduct::create(['type' => 'price_option']);
  }

}
