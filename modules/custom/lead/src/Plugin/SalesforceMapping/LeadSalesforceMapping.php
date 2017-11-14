<?php

namespace Drupal\lead\Plugin\SalesforceMapping;

use Drupal\Core\Entity\EntityInterface;
use Drupal\master\Master;
use Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase;
use Drupal\salesforce\SalesforceException;
use Drupal\user\Entity\User;

/**
 * Class LeadSalesforceMapping
 *
 * @SalesforceMapping(
 *   id = "lead",
 *   label = @Translation("Mapping lead entity"),
 *   entity_type_id = "lead",
 *   salesforce_object = "Lead",
 *   entity_operations = {"update"},
 *   object_operations = {"delete"},
 *   priority = "drupal"
 * )
 */
class LeadSalesforceMapping extends SalesforceMappingBase {

  const
    LEAD_RECORD_TYPE = 'Sign up';

  /**
   * {@inheritdoc}
   */
  public function getQueryConditions() {
    $conditions[] = [
      'field' => 'Site__c',
      'value' => "'" . Master::siteCode() . "'",
      'operator' => '=',
    ];

    return $conditions;
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\lead\Entity\Lead $lead
   */
  protected function doImport(EntityInterface $lead, \stdClass $record) {
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\lead\Entity\Lead $lead
   */
  protected function doExport(EntityInterface $lead, \stdClass $record) {
    if (!$this->mappingObject->getRecordId()) {
      $record->RecordTypeId = $this->getRecordTypeId();
    }

    $record->FirstName = $lead->getFirstName();
    $record->LastName = $lead->getLastName();
    $record->Email = $lead->getEmail();
    $record->Site__c = Master::siteCode();
    $record->IP_address__c = $lead->getData('ip');
    $record->IP_country__c = $lead->getData('country');
    $record->IP_city__c = $lead->getData('city');
    $record->Saved_search__c = $lead->getData('search_url');
    $record->RT_departure__c = $lead->getData('departure_date')->format('c');
    $record->Description = $lead->getData('route');
    $record->Language__c = $lead->getData('current_language_code');
    $record->LeadSource = $lead->getData('lead_source');
    $record->Sign_up_source__c = $lead->getData('sign_up_source');

    return $record;
  }

  /**
   * {@inheritdoc}
   */
  public function isSyncAllowed($action) {
    return true;
  }

  /**
   * Gets appropriate record type id.
   *
   * @return string
   */
  protected function getRecordTypeId() {
    return array_search(static::LEAD_RECORD_TYPE, $this->getRecordTypes());
  }

}
