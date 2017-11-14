<?php

namespace Drupal\payment\Plugin\SalesforceMapping;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\master\Master;
use Drupal\payment\Entity\Transaction;
use Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase;
use Drupal\salesforce\SalesforceException;
use Drupal\salesforce\SalesforceSync;

/**
 * Class TransactionSalesforceMapping
 *
 * @SalesforceMapping(
 *   id = "transaction_payment_transaction",
 *   label = @Translation("Mapping of Transaction to Payment_transaction__c"),
 *   entity_type_id = "transaction",
 *   salesforce_object = "Payment_transaction__c",
 *   entity_operations = {"update", "delete"},
 *   object_operations = {},
 *   priority = "drupal"
 * )
 */
class TransactionSalesforceMapping extends SalesforceMappingBase {

  const
    RECORD_TYPE_FIRSTPAYMENTS = 'FirstPayments',
    RECORD_TYPE_PAYPAL = 'Paypal',
    RECORD_TYPE_WALLET_ONE = 'WalletOne',
    RECORD_TYPE_ECOMMPAY = 'Ecommpay',
    RECORD_TYPE_PAYSERA = 'Paysera';

  /**
   * {@inheritdoc}
   * @param \Drupal\payment\Entity\Transaction $transaction
   */
  protected function doImport(EntityInterface $transaction, \stdClass $record) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\payment\Entity\Transaction $transaction
   */
  protected function doExport(EntityInterface $transaction, \stdClass $record) {
    $invoiceMappingObject = $this->assureExport($transaction->getInvoice(), 'invoice_invoice');
    if (!$invoiceMappingObject || !$invoiceMappingObject->getRecordId()) {
      throw new SalesforceException('Related invoice has not been exported yet.');
    }

    $dateChanged = new DrupalDateTime('now', new \DateTimeZone('UTC'));
    $dateChanged->setTimestamp($transaction->getChangedTime());
    $dateCreated = new DrupalDateTime('now', new \DateTimeZone('UTC'));
    $dateCreated->setTimestamp($transaction->getCreatedTime());

    $record->Name                 = Master::siteCode() . '-' . $transaction->id();
    $record->Invoice__c           = $invoiceMappingObject->getRecordId();
    $record->Status__c            = $transaction->getStatus();
    $record->CurrencyIsoCode      = $transaction->getAmount()->getCurrencyCode();
    $record->Amount__c            = $transaction->getAmount()->getNumber();
    $record->Currency_rate__c     = $transaction->getCurrencyRate();
    $record->Client_IP_address__c = $transaction->getIPAddress();
    $record->Merchant_ID__c       = $transaction->getMerchant()->getMerchantId();
    $record->Payment_by__c        = $this->getPaymentBy($transaction);
    $record->Transaction_type__c  = $this->getTransactionType($transaction);
    $record->Updated_datetime__c  = $dateChanged->format('c');
    $record->Created_datetime__c  = $dateCreated->format('c');

    if ($transaction->getStatus() == Transaction::STATUS_FAILED) {
      $record->Error_messages__c = $transaction->getMessage();
    }

    switch ($transaction->getMerchant()->getPaymentAdapter()) {
      case 'paypal_ec':
      case 'paypal_wpp':
        $record->Paypal_transaction_ID__c = $transaction->getRemoteId();
    }

    if (!$this->mappingObject->getRecordId()) {
      if (!$id = $this->getRecordTypeId($transaction)) {
        throw new SalesforceException('Can\'t get appropriate record type id for transaction.');
      }
      $record->RecordTypeId = $id;
    }

    return $record;
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
    /** @var \Drupal\payment\Entity\Transaction $transaction */
    $transaction = $this->getEntity();
    return ($action == SalesforceSync::SYNC_ACTION_DELETE || $transaction->getInvoice()) && $transaction->getMerchant()->getPaymentAdapter() != 'simple';
  }

  /**
   * Gets appropriate record type for the transaction being exported.
   *
   * @param \Drupal\payment\Entity\Transaction $transaction
   * @return string|null
   */
  protected function getRecordTypeId(Transaction $transaction) {
    $recordTypes = $this->getRecordTypes();
    $id = null;
    switch ($transaction->getMerchant()->getPaymentAdapter()) {
      case 'paypal_ec':
      case 'paypal_wpp':
        $id = array_search(static::RECORD_TYPE_PAYPAL, $recordTypes);
        break;
      case 'ecommpay_3ds_card':
      case 'ecommpay_non3ds_card':
        $id = array_search(static::RECORD_TYPE_ECOMMPAY, $recordTypes);
        break;
      case 'paysera':
        $id = array_search(static::RECORD_TYPE_PAYSERA, $recordTypes);
        break;
    }

    return $id;
  }

  /**
   * Gets payment by string.
   *
   * @param \Drupal\payment\Entity\Transaction $transaction
   * @return string
   */
  protected function getPaymentBy(Transaction $transaction) {
    switch ($transaction->getPaymentMethod()) {
      case 'credit_card':
        return 'Credit card';
      case 'paypal':
        return 'PayPal';
      default:
        return str_replace('_', ' ', $transaction->getPaymentMethod());
    }
  }

  /**
   * Gets transaction type name.
   *
   * @param \Drupal\payment\Entity\Transaction $transaction
   * @return string
   */
  protected function getTransactionType(Transaction $transaction) {
    switch ($transaction->getType()) {
      case Transaction::TYPE_PAYMENT:
        return 'payment';
      case Transaction::TYPE_REFUND:
        return 'refund';
    }
  }

}
