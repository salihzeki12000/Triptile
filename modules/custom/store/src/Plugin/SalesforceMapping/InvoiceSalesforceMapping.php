<?php

namespace Drupal\store\Plugin\SalesforceMapping;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\master\Master;
use Drupal\payment\Entity\Transaction;
use Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase;
use Drupal\salesforce\SalesforceException;
use Drupal\store\Entity\Invoice;
use Drupal\store\Entity\StoreOrder;
use Drupal\user\Entity\User;

/**
 * Class InvoiceSalesforceMapping
 *
 * @SalesforceMapping(
 *   id = "invoice_invoice",
 *   label = @Translation("Mapping of Invoice to Invoice__c"),
 *   entity_type_id = "invoice",
 *   salesforce_object = "Invoice__c",
 *   entity_operations = {"update", "delete"},
 *   object_operations = {"update", "delete"},
 *   priority = "drupal"
 * )
 */
class InvoiceSalesforceMapping extends SalesforceMappingBase {

  const
    REFUND_RECORD_TYPE = 'Refund',
    INVOICE_RECORD_TYPE = 'Invoice',
    RECEIPT_RECORD_TYPE = 'Receipt';

  const
    RECEIVABLE_INVOICE_TYPE = 'Receivable',
    PAYABLE_INVOICE_TYPE = 'Payable',
    RECONCILLIATION_INVOICE_TYPE = 'Reconcilliation',
    CREDIT_NOTE_INVOICE_TYPE = 'Credit note';

  public function getImportFields() {
    $fields = [
      'Account__r.Id',
      'Opportunity__r.Id',
      'Status__c',
      'ExtID__c',
      'Name',
      'Amount__c',
      'CurrencyIsoCode',
      'Invoice_notes__c',
      'Site_publish__c',
      'Due_date__c',
    ];

    // @todo Add import of next fields.
    // Item_N__c
    // Quantity_N__c
    // Item_N_price__c
    // Travelers__c

    return array_merge(parent::getImportFields(), $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function export() {
    $record = parent::export();
    if (!$this->mappingObject->getRecordId()) {
      $response = $this->salesforceApi->getRecord($record->Id, $this->pluginDefinition['salesforce_object']);
      $this->getEntity()->setInvoiceNumber($response->Name)->save();
    }

    return $record;
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\store\Entity\Invoice $invoice
   */
  protected function doImport(EntityInterface $invoice, \stdClass $record) {
    $userMappingObject = $this->assureImport($record->Account__r->Id, 'user_account');
    if (!$userMappingObject || !$userMappingObject->getMappedEntityId()) {
      throw new SalesforceException('Invoice owner has not been imported yet.');
    }
    $invoice->setUser(User::load($userMappingObject->getMappedEntityId()));

    if ($record->Opportunity__r->Id) {
      $orderMappingObject = $this->assureImport($record->Opportunity__r->Id, 'store_order_opportunity');
      if ($orderMappingObject && $orderMappingObject->getMappedEntityId()) {
        $invoice->setOrder(StoreOrder::load($orderMappingObject->getMappedEntityId()));
      }
    }

    // Disallow to change status of paid invoice.
    if ($invoice->getStatus() != Invoice::STATUS_PAID && $status = $this->salesforceToLocalInvoiceStatus($record->Status__c)) {
      $invoice->setStatus($status);
    }

    if ($record->Due_date__c) {
      $invoice->setExpirationDate(new DrupalDateTime($record->Due_date__c));
    }

    $price = \Drupal::service('store.price')->get($record->Amount__c, $record->CurrencyIsoCode);

    $invoice->setInvoiceNumber($record->ExtID__c ? $record->ExtID__c : $record->Name);
    $invoice->setAmount($price);
    $invoice->setDescription($record->Invoice_notes__c);
    $invoice->setVisibility($record->Site_publish__c);

  }

  /**
   * {@inheritdoc}
   * @param \Drupal\store\Entity\Invoice $invoice
   */
  protected function doExport(EntityInterface $invoice, \stdClass $record) {
    $userMappingObject = $this->assureExport($invoice->getUser(), 'user_account');
    if (!$userMappingObject || !$userMappingObject->getRecordId()) {
      throw new SalesforceException('Invoice owner has not been exported yet.');
    }

    if ($order = $invoice->getOrder()) {
      $orderMappingObject = $this->assureExport($order, 'store_order_opportunity');
      if (!$orderMappingObject || !$orderMappingObject->getRecordId()) {
        throw new SalesforceException('Related order has not been exported.');
      }
      $record->Opportunity__c = $orderMappingObject->getRecordId();
    }

    $record->Site__c = Master::siteCode();
    $record->Account__c = $userMappingObject->getRecordId();
    $record->CurrencyIsoCode = $invoice->getAmount()->getCurrencyCode();
    $record->Amount__c = $invoice->getAmount()->getNumber();
    $record->Status__c = $this->localToSalesforceInvoiceStatus($invoice->getStatus());
    $record->Site_request__c = true;

    if ($invoice->getExpirationDate()) {
      $record->Due_date__c = $invoice->getExpirationDate()->format('c');
    }

    if ($invoice->getStatus() == Invoice::STATUS_PAID) {
      $record->Paid__c = $invoice->getAmount()->getNumber();
    }

    if (!$this->mappingObject->getRecordId()) {
      $record->RecordTypeId = $this->getRecordTypeId($invoice);
      if ($invoice->getAmount()->getNumber() < 0) {
        $record->Invoice_type__c = static::CREDIT_NOTE_INVOICE_TYPE;
      }
      else {
        $record->Explanation__c = 'Final payment';
        $record->Counterparty__c = 'Customer';
        $record->Invoice_type__c = static::RECEIVABLE_INVOICE_TYPE;
      }
    }

    $record = $this->exportTransactionInfo($invoice, $record);
    $record = $this->exportBillingInfo($invoice, $record);

    // @todo Related logic is not implemented yet.
    // Item_N__c
    // Quantity_N__c
    // Item_C_price__c

    // @todo Do we still need this fields?
    // $record->Security_hash__c = $invoice->getHash();
    // $record->Notes__c
    // $record->Site_link__c

    return $record;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryConditions() {
    $conditions = [];
    $conditions[] = [
      'field' => 'Site__c',
      'value' => "'" . Master::siteCode() . "'",
      'operator' => '=',
    ];

    $conditions[] = [
      'field' => 'Invoice_type__c',
      'value' => "'" . static::RECEIVABLE_INVOICE_TYPE . "'",
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
   * Exports data from transaction related to the invoice.
   *
   * @param \Drupal\store\Entity\Invoice $invoice
   * @param \stdClass $record
   * @return \stdClass
   */
  protected function exportTransactionInfo(Invoice $invoice, \stdClass $record) {
    $transaction = $this->getTransactionWithStatus($invoice, Transaction::STATUS_SUCCESS);
    $transaction = $transaction ?: $this->getTransactionWithStatus($invoice, Transaction::STATUS_PARTIALLY_REFUNDED);
    $transaction = $transaction ?: $this->getTransactionWithStatus($invoice, Transaction::STATUS_REFUNDED);

    if ($transaction) {
      $datePaid = new DrupalDateTime('now', new \DateTimeZone('UTC'));
      $datePaid->setTimestamp($transaction->getCreatedTime());
      $merchant = $transaction->getMerchant();

      $record->Date_paid__c = $datePaid->format('c');
      $record->Payment_by__c = $this->getPaymentBy($transaction);
      $record->Transaction_ID__c = $transaction->getRemoteId();
      $record->Merchant_ID__c = $merchant->getMerchantId();
      $record->AG__c = $merchant->getCompanyId();

      if (in_array($merchant->getPaymentAdapter(), ['paypal_ec', 'paypal_wpp'])) {
        foreach ($transaction->getLog() as $item) {
          if (isset($item['AVSCODE'])) {
            $record->AVS__c = $item['AVSCODE'];
          }
          if (isset($item['CVV2MATCH'])) {
            $record->CSC__c = $item['CVV2MATCH'];
          }
        }
      }
    }

    return $record;
  }

  /**
   * Exports data from related billing profile.
   *
   * @param \Drupal\store\Entity\Invoice $invoice
   * @param \stdClass $record
   * @return \stdClass
   */
  protected function exportBillingInfo(Invoice $invoice, \stdClass $record) {
    $customerProfile = $invoice->getCustomerProfile();
    if ($customerProfile) {
      $address = $customerProfile->getAddress();
      $details = sprintf('Billing details: %s %s %s %s', $address->getGivenName(),
        $address->getAdditionalName(), $address->getFamilyName(), $customerProfile->getEmail()
      );
      $record->Transaction_details__c = $details;
    }

    return $record;
  }

  /**
   * Gets appropriate record type id for the exported invoice.
   *
   * @param \Drupal\store\Entity\Invoice $invoice
   *
   * @return false|int|null|string
   */
  protected function getRecordTypeId(Invoice $invoice) {
    $recordTypes = $this->getRecordTypes();
    $id = null;
    if ($invoice->getAmount()->getNumber() < 0) {
      $id = array_search(static::REFUND_RECORD_TYPE, $recordTypes);
    }
    elseif ($invoice->getStatus() == Invoice::STATUS_UNPAID) {
      $id = array_search(static::INVOICE_RECORD_TYPE, $recordTypes);
    }
    else {
      $id = array_search(static::RECEIPT_RECORD_TYPE, $recordTypes);
    }

    return $id;
  }

  /**
   * Converts invoice status in salesforce to status in local database.
   *
   * @param $status
   * @return bool|string
   */
  protected function salesforceToLocalInvoiceStatus($status) {
    switch ($status) {
      case 'UNPAID':
        return Invoice::STATUS_UNPAID;

      case 'PENDING':
        return Invoice::STATUS_PENDING;

      case 'ISSUED':
        return Invoice::STATUS_ISSUED;

      case 'CANCELED':
        return Invoice::STATUS_CANCELED;

      case 'FAILED':
        return Invoice::STATUS_FAILED;

      case 'PAID':
        return Invoice::STATUS_PAID;

      case 'CLEARING':
        return Invoice::STATUS_CLEARING;

      case 'PRELIMINARY':
        return Invoice::STATUS_PRELIMINARY;

      case 'AUTHORIZED':
        return Invoice::STATUS_AUTHORIZED;

      case 'CHARGEBACK':
        return Invoice::STATUS_CHARGEBACK;

      default:
        return false;
    }
  }

  /**
   * Converts local invoice status to invoice status in salesforce.
   *
   * @param $status
   * @return string|bool
   */
  protected function localToSalesforceInvoiceStatus($status) {
    switch ($status) {
      case Invoice::STATUS_UNPAID:
        return 'UNPAID';

      case Invoice::STATUS_PENDING:
        return 'PENDING';

      case Invoice::STATUS_ISSUED:
        return 'ISSUED';

      case Invoice::STATUS_CANCELED:
        return 'CANCELED';

      case Invoice::STATUS_FAILED:
        return 'FAILED';

      case Invoice::STATUS_PAID:
        return 'PAID';

      case Invoice::STATUS_CLEARING:
        return 'CLEARING';

      case Invoice::STATUS_PRELIMINARY:
        return 'PRELIMINARY';

      case Invoice::STATUS_AUTHORIZED:
        return 'AUTHORIZED';

      case Invoice::STATUS_CHARGEBACK:
        return 'CHARGEBACK';

      default:
        return false;
    }
  }

  /**
   * Gets string for Payment by field on invoice in SF.
   *
   * @param \Drupal\payment\Entity\Transaction $transaction
   *
   * @return string
   */
  protected function getPaymentBy(Transaction $transaction) {
    return str_replace('_', ' ', $transaction->getPaymentMethod());
  }

  /**
   * Gets a transaction with the $status from the invoice.
   *
   * @param \Drupal\store\Entity\Invoice $invoice
   * @param $status
   *
   * @return \Drupal\payment\Entity\Transaction|null
   */
  protected function getTransactionWithStatus(Invoice $invoice, $status) {
    foreach ($invoice->getTransactions() as $transaction) {
      if ($transaction->getStatus() == $status) {
        return $transaction;
      }
    }

    return null;
  }

}
