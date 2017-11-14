<?php

namespace Drupal\tt_config\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ExportLead extends ControllerBase{

  const
    RECORD_TYPE_SIGN_UP_ID = '012800000003Gf0AAE'; //Id for sign up record type

  /*
   * Export for save and share form
   */
  public function saveAndShare(Request $request){
    $lead = $request->getContent();
    $decode = json_decode($lead);

    $record = (object) array(
      'LastName' => $decode->name,
      'Sign_up_source__c' => 'Save & Share',
      'Site__c' => 'TT',
      'IP_country__c' => $decode->ip,
      'Email_1__c' => $decode->email1,
      'Email_2__c' => $decode->email2,
      'Email_3__c' => $decode->email3,
      'Email_4__c' => $decode->email4,
      'Email_5__c' => $decode->email5,
      'RecordTypeId' => static::RECORD_TYPE_SIGN_UP_ID,
      'Phone' => $decode->phone,
      'Price__c' => $decode->total,
      'PAX__c' => $decode->whoGo,
      'RT_departure__c' => $decode->whenGo,
      'Description' => $decode->description,
      'Triptile_link__c' => $decode->link
    );

    $salesforseApi = \Drupal::service('salesforce_api');
    $create = $salesforseApi->createRecord('Lead', $record);

    return new JsonResponse($create);
  }

  /*
   * Export for book now form
   */
  public function bookNow(Request $request){
    $lead = $request->getContent();
    $decode = json_decode($lead);

    $record = (object) array(
      'LastName' => $decode->name,
      'Sign_up_source__c' => 'Book now',
      'Site__c' => 'TT',
      'IP_country__c' => $decode->ip,
      'Email' => $decode->email,
      'RecordTypeId' => static::RECORD_TYPE_SIGN_UP_ID,
      'Phone' => $decode->phone,
      'Price__c' => $decode->total,
      'PAX__c' => $decode->whoGo,
      'RT_departure__c' => $decode->whenGo,
      'Description' => $decode->description,
      'Triptile_link__c' => $decode->link
    );

    $salesforseApi = \Drupal::service('salesforce_api');
    $create = $salesforseApi->createRecord('Lead', $record);

    return new JsonResponse($create);
  }

  /*
   * Export for subscribe form
   */
  public function subscribe(Request $request){
    $lead = $request->getContent();
    $decode = json_decode($lead);

    $record = (object) array(
      'LastName' => 'Lead from newsletter form',
      'Sign_up_source__c' => 'Newsletter form',
      'Site__c' => 'TT',
      'IP_country__c' => $decode->ip,
      'Email' => $decode->email,
      'RecordTypeId' => static::RECORD_TYPE_SIGN_UP_ID,
    );

    $salesforseApi = \Drupal::service('salesforce_api');
    $create = $salesforseApi->createRecord('Lead', $record);

    return new JsonResponse($create);
  }

}