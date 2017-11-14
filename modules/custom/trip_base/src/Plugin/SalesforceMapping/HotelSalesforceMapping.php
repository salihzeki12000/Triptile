<?php

namespace Drupal\trip_base\Plugin\SalesforceMapping;

use Drupal\Core\Entity\EntityInterface;
use Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase;
use Drupal\salesforce\SelectQuery;
use Drupal\salesforce\SalesforceException;
use Drupal\trip_base\Entity\Hotel;

/**
 * Class HotelSalesforceMapping
 *
 * @SalesforceMapping(
 *   id = "hotel_account",
 *   label = @Translation("Mapping of Hotels to Hotel accounts"),
 *   entity_type_id = "hotel",
 *   salesforce_object = "Account",
 *   entity_operations = {},
 *   object_operations = {"update", "delete"},
 *   priority = "salesforce"
 * )
 */
class HotelSalesforceMapping extends SalesforceMappingBase {

  const
    ACCOUNT_HOTEL_TYPE = 'Hotel';

  /**
   * {@inheritdoc}
   */
  public function getImportFields() {
    $fields = [
      'Name',
      'Greeting__c',
      'Rating',
      'BillingCountry',
      'BillingCity',
      'BillingStreet',
      'Hub_hotel__c',
      'Preferred__c',
    ];

    return array_merge(parent::getImportFields(), $fields);
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\trip_base\Entity\Hotel $hotel
   */
  protected function doImport(EntityInterface $hotel, \stdClass $record) {
    if($record->Hub_hotel__c){
      $hubMappingObject = $this->assureImport($record->Hub_hotel__c, 'hub_hub');
      if (!$hubMappingObject || !$hubMappingObject->getMappedEntityId()) {
        throw new SalesforceException('Hub has not been imported yet');
      }
      $hotel->setHub($hubMappingObject->getMappedEntityId());
    }


    $hotel->setName($record->Name)
      ->setStar($record->Rating);

    $hotel->setAddress($record->BillingCountry, $record->BillingCity, $record->BillingStreet);

    $hotel->setPreferred($record->Preferred__c);

    $this->importPriceOptions($hotel, $record);
  }

  /**
   * {@inheritdoc}
   */
  protected function doExport(EntityInterface $entity, \stdClass $record) {
    // Do nothing here.
  }

  /**
  /**
   * {@inheritdoc}
   */
  public function getQueryConditions() {
    $conditions = [];

    $conditions[] = [
      'field' => 'Type',
      'value' => "'" . static::ACCOUNT_HOTEL_TYPE . "'",
      'operator' => '=',
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
   * Imports price options for the hotel.
   *
   * @param \Drupal\trip_base\Entity\Hotel $hotel
   * @param \stdClass $record
   */
  protected function importPriceOptions(Hotel $hotel, \stdClass $record) {
    if (!empty($record->Id)) {
      /** @var \Drupal\salesforce\Plugin\SalesforceMappingManager $salesforceMappingManager */
      $salesforceMappingManager = \Drupal::service('plugin.manager.salesforce_mapping');
      /** @var \Drupal\store\Plugin\SalesforceMapping\BaseProductSalesforceMapping $mappingPlugin */
      $mappingPlugin = $salesforceMappingManager->createInstance('base_product_price');

      $query = new SelectQuery('Price__c');
      $query->condition('Standard_Service__r.Account__r.Id', "'" . $record->Id . "'")
        ->field($mappingPlugin->getImportFields());
      $prices = $this->salesforceApi->query($query);

      $ids = [];
      foreach ($prices as $price) {
        $mappingObject = $this->assureImport($price->Id, 'base_product_price', $price);
        $ids[] = $mappingObject->getMappedEntityId();
      }

      $hotel->setPriceOptionsIds($ids);
    }
  }

}
