<?php

namespace Drupal\rn_user\Plugin\SalesforceMapping;

use Drupal\Core\Entity\EntityInterface;
use Drupal\master\Master;
use Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase;
use Drupal\salesforce\SalesforceException;
use Drupal\user\Entity\User;

/**
 * Class UserSalesforceMapping
 *
 * @SalesforceMapping(
 *   id = "user_account",
 *   label = @Translation("Mapping of drupal user to Account"),
 *   entity_type_id = "user",
 *   salesforce_object = "Account",
 *   entity_operations = {"update", "delete"},
 *   object_operations = {"update", "delete"},
 *   priority = "drupal"
 * )
 */
class UserSalesforceMapping extends SalesforceMappingBase {

  const
    CUSTOMER_RECORD_TYPE = 'Customer',
    GROUP_RECORD_TYPE = 'Group customer',
    PARTNER_RECORD_TYPE = 'Partner',
    PERSON_BUSINESS_RECORD_TYPE = 'Person Business';

  const
    ACCOUNT_TYPE_CUSTOMER = 'Customer',
    ACCOUNT_TYPE_TRAVEL_AGENT = 'Travel agent',
    ACCOUNT_TYPE_GROUP = 'Group',
    ACCOUNT_TYPE_GUIDE = 'Guide';

  protected static $upsertField = 'ExtId__c';

  /**
   * {@inheritdoc}
   */
  public function getQueryConditions() {
    return [];
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
      elseif ($record = $this->getRecord()) {
        $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $record->ExtId__c]);
        if (!empty($users)) {
          $this->entity = reset($users);
        }
      }
    }

    return $this->entity;
  }

  public function getImportFields() {
    $fields = [
      'ExtId__c',
    ];

    return array_merge(parent::getImportFields(), $fields);
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\user\Entity\User $user
   */
  protected function doImport(EntityInterface $user, \stdClass $record) {
    $user->setEmail($record->ExtId__c);
    $user->setUsername($record->ExtId__c);
    $user->activate();
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\rn_user\Entity\User $user
   */
  protected function doExport(EntityInterface $user, \stdClass $record) {
    $customer_profile = $user->getCustomerProfile();

    $isPersonAccount = true;
    if ($this->mappingObject->getRecordId()) {
      $sfAccount = $this->salesforceApi->getRecord($this->mappingObject->getRecordId(), 'Account');
      $recordTypes = $this->getRecordTypes();
      if (array_search(static::CUSTOMER_RECORD_TYPE, $recordTypes) != $sfAccount->RecordTypeId) {
        $isPersonAccount = false;
      }
    }

    $record->Site__c = Master::siteCode();
    $record->Language__c = $user->language()->getId();
    $record->ExtId__c = $user->getEmail();
    // PersonalEmail only personal account fields.
    if ($isPersonAccount) {
      $record->PersonEmail = $user->getEmail();
    }


    if ($customer_profile) {
      $address = $customer_profile->getAddress();
      // @todo Get this data from user object when fields are added.
      $record->Phone = $customer_profile->getPhoneNumber();
      $record->BillingCity = $address->getLocality();
      $record->BillingCountry = $address->getCountryCode();
      $record->BillingPostalCode = $address->getPostalCode();
      $record->BillingState = $address->getAdministrativeArea();
      $record->BillingStreet = $address->getAddressLine1();

      // FirstName and LastName are only personal account fields.
      if ($isPersonAccount) {
        $record->FirstName = $address->getGivenName();
        $record->LastName = $address->getFamilyName();
      }
    }
    else {
      $record->LastName = $user->getAccountName();
    }

    if (!$this->mappingObject->getRecordId()) {
      $record->RecordTypeId = $this->getRecordTypeId($user);
      $record->Type = static::ACCOUNT_TYPE_CUSTOMER;
      if ($user->isActive()) {
        $record->Site_registered__c = true;
      }
      else {
        $record->Site_registered__c = false;
      }
    }

    // @todo Related logic hasn't been implemented yet.
    // $record->Monthly_invoicing__c
    // $record->Referrer__c

    // @todo Do we still need them?
    // $record->Registration_link__c = Url::fromRoute('user.pass', ['language' => $user->language()])->setAbsolute()->toString();
    // $record->Bank_details__c
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
   * @param \Drupal\user\Entity\User $user
   * @return string
   */
  public function getRecordTypeId(User $user) {
    return array_search(static::CUSTOMER_RECORD_TYPE, $this->getRecordTypes());
  }

}