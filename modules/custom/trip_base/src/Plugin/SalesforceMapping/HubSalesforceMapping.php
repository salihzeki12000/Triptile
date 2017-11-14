<?php

namespace Drupal\trip_base\Plugin\SalesforceMapping;

use Drupal\Core\Entity\EntityInterface;
use Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase;

/**
 * Class HubSalesforceMapping
 *
 * @SalesforceMapping(
 *   id = "hub_hub",
 *   label = @Translation("Mapping of Hub to Hub__c"),
 *   entity_type_id = "hub",
 *   salesforce_object = "Hub__c",
 *   entity_operations = {},
 *   object_operations = {"update", "delete"},
 *   priority = "salesforce"
 * )
 */
class HubSalesforceMapping extends SalesforceMappingBase {

  /**
   * {@inheritdoc}
   */
  public function getImportFields() {
    $fields = [
      'Name',
      'Location_rating__c',
      'Country_code__c',
      'Recommended_days__c',
    ];

    return array_merge(parent::getImportFields(), $fields);
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\trip_base\Entity\Hub $hub
   */
  protected function doImport(EntityInterface $hub, \stdClass $record) {
    $hub->setName($record->Name)
      ->setRating($record->Location_rating__c)
      ->setCountry($record->Country_code__c)
      ->setRecommendedNumberOfDays($record->Recommended_days__c);
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isSyncAllowed($action) {
    return true;
  }

}
