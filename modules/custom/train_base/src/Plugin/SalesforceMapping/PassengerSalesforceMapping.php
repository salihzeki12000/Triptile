<?php

namespace Drupal\train_base\Plugin\SalesforceMapping;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\master\Master;
use Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase;
use Drupal\salesforce\SalesforceException;
use Drupal\salesforce\SalesforceSync;

/**
 * Class PassengerSalesforceMapping
 *
 * @SalesforceMapping(
 *   id = "passenger_traveler",
 *   label = @Translation("Mapping of Passenger to Traveler__c"),
 *   entity_type_id = "passenger",
 *   salesforce_object = "Traveler__c",
 *   entity_operations = {"update", "delete"},
 *   object_operations = {"update"},
 *   priority = "drupal"
 * )
 */
class PassengerSalesforceMapping extends SalesforceMappingBase {

  /**
   * Mapping of drupal gender kes to salesforce gender keys.
   *
   * @var array
   */
  protected static $genderMapping = [
    'mr' => 'Mr.',
    'mrs' => 'Mrs.',
    'miss' => 'Miss.',
  ];

  /**
   * {@inheritdoc}
   */
  public function getImportFields() {
    $fields = [
      'First_Name__c',
      'Last_Name__c',
      'Sex__c',
      'Passport_number__c',
      'Date_of_birth__c',
      'Citizenship__c',
      'Title__c',
    ];

    return array_merge(parent::getImportFields(), $fields);
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\train_base\Entity\Passenger $passenger
   */
  protected function doImport(EntityInterface $passenger, \stdClass $record) {
    $passenger->setFirstName($record->First_Name__c)
      ->setLastName($record->Last_Name__c);

    switch ($record->Sex__c) {
      case 'M':
        $passenger->setGender('male');
        break;
      case 'F':
        $passenger->setGender('female');
    }

    if ($record->Passport_number__c) {
      $passenger->setIdNumber($record->Passport_number__c);
    }

    if ($record->Date_of_birth__c) {
      $passenger->setDob(new DrupalDateTime($record->Date_of_birth__c));
    }

    if ($record->Citizenship__c) {
      $code = array_search($record->Citizenship__c, \Drupal::service('country_manager')->getList());
      if ($code) {
        $passenger->setCitizenship($code);
      }
    }

    if ($record->Title__c) {
      $title = array_search($record->Title__c, static::$genderMapping);
      if ($title) {
        $passenger->setTitle($title);
      }
    }
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\train_base\Entity\Passenger $passenger
   */
  protected function doExport(EntityInterface $passenger, \stdClass $record) {
    $orderMappingObject = $this->assureExport($passenger->getTicket()->getOrder(), 'store_order_opportunity');
    if (!$orderMappingObject && !$orderMappingObject->getRecordId()) {
      throw new SalesforceException('Related order has not been exported.');
    }

    if (!$this->mappingObject->getRecordId()) {
      $record->Opportunity__c = $orderMappingObject->getRecordId();
    }
    $record->First_Name__c = $passenger->getFirstName();
    $record->Last_Name__c = $passenger->getLastName();

    if ($passenger->getGender()) {
      $record->Sex__c = strtoupper(substr($passenger->getGender(), 0, 1));
    }
    if ($passenger->getIdNumber()) {
      $record->Passport_number__c = $passenger->getIdNumber();
    }
    if ($passenger->getDob()) {
      $record->Date_of_birth__c = $passenger->getDob()->format('c', ['timezone' => 'UTC']);
    }
    if ($passenger->getCitizenship()) {
      $record->Citizenship__c = \Drupal::service('country_manager')->getList()[$passenger->getCitizenship()];
    }
    if ($passenger->getTitle()) {
      $record->Title__c = static::$genderMapping[$passenger->getTitle()];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryConditions() {
    $conditions = [];
    $conditions[] = [
      'field' => 'Opportunity__r.Site__c',
      'value' => "'" . Master::siteCode() . "'",
      'operator' => '=',
    ];

    return $conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function isSyncAllowed($action) {
    if ($action == SalesforceSync::SYNC_ACTION_PULL) {
      if (!$this->mappingObject) {
        throw new SalesforceException('Salesforce mapping object is not set.');
      }

      // Do not import new passengers
      if ($this->getEntity()) {
        $order = $this->getEntity()->getTicket()->getOrder();
        return (bool) $this->mappingObject->getMappedEntityId() && $order;
      }
      else {
        return false;
      }
    }
    return true;
  }

}