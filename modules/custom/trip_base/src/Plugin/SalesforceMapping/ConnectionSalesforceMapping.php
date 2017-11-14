<?php

namespace Drupal\trip_base\Plugin\SalesforceMapping;

use Drupal\Core\Entity\EntityInterface;
use Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase;
use Drupal\salesforce\SalesforceException;
use Drupal\salesforce\SelectQuery;
use Drupal\trip_base\Entity\Connection;

/**
 * Class ConnectionSalesforceMapping
 *
 * @SalesforceMapping(
 *   id = "connection_connections",
 *   label = @Translation("Mapping of Connection to Connections__c"),
 *   entity_type_id = "connection",
 *   salesforce_object = "Connections__c",
 *   entity_operations = {},
 *   object_operations = {"update", "delete"},
 *   priority = "salesforce"
 * )
 */
class ConnectionSalesforceMapping extends SalesforceMappingBase {

  protected static $typeMapping = [
    'Air' => Connection::TYPE_AIR,
    'Rail' => Connection::TYPE_RAIL,
    'Ferry' => Connection::TYPE_FERRY,
    'Car' => Connection::TYPE_CAR,
    'Walk' => Connection::TYPE_WALK,
    'Bus' => Connection::TYPE_BUS,
    'Subway' => Connection::TYPE_SUBWAY,
  ];

  /**
   * {@inheritdoc}
   */
  public function getImportFields() {
    $fields = [
      'Name',
      'A__c',
      'B__c',
      'Type__c',
      'Rating__c',
      'Overall_rating__c',
    ];

    return array_merge(parent::getImportFields(), $fields);
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\trip_base\Entity\Connection $connection
   */
  protected function doImport(EntityInterface $connection, \stdClass $record) {
    $hubAMappingObject = $this->assureImport($record->A__c, 'hub_hub');
    if (!$hubAMappingObject || !$hubAMappingObject->getMappedEntityId()) {
      throw new SalesforceException('Point A has not been imported yet');
    }
    $connection->setPointAId($hubAMappingObject->getMappedEntityId());

    $hubBMappingObject = $this->assureImport($record->B__c, 'hub_hub');
    if (!$hubBMappingObject || !$hubBMappingObject->getMappedEntityId()) {
      throw new SalesforceException('Point B has not been imported yet');
    }
    $connection->setPointBId($hubBMappingObject->getMappedEntityId());

    $connection->setName($record->Name)
      ->setType($record->Type__c ? static::$typeMapping[$record->Type__c] : null)
      ->setRating($record->Rating__c)
      ->setOverallRating($record->Overall_rating__c);

    $this->importPriceOptions($connection, $record);
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\trip_base\Entity\Connection $connection
   */
  protected function doExport(EntityInterface $connection, \stdClass $record) {
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

  /**
   * Imports price options for the Connection.
   *
   * @param \Drupal\trip_base\Entity\connection $connection
   * @param \stdClass $record
   */
  protected function importPriceOptions(Connection $connection, \stdClass $record) {
    if (!empty($record->Id)) {
      /** @var \Drupal\salesforce\Plugin\SalesforceMappingManager $salesforceMappingManager */
      $salesforceMappingManager = \Drupal::service('plugin.manager.salesforce_mapping');
      /** @var \Drupal\store\Plugin\SalesforceMapping\BaseProductSalesforceMapping $mappingPlugin */
      $mappingPlugin = $salesforceMappingManager->createInstance('base_product_price');

      $query = new SelectQuery('Price__c');
      $query->condition('Standard_Service__r.Connection__r.Id', "'" . $record->Id . "'")
        ->field($mappingPlugin->getImportFields());
      $prices = $this->salesforceApi->query($query);

      $ids = [];
      foreach ($prices as $price) {
        $mappingObject = $this->assureImport($price->Id, 'base_product_price', $price);
        $ids[] = $mappingObject->getMappedEntityId();
      }

      $connection->setPriceOptionsIds($ids);
    }
  }

}
