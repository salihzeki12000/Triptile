<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\payment\API\PaypalAPI;
use \Drupal\payment\Entity\Transaction as TransactionEntity;

/**
 * Source plugin for the Transaction.
 *
 * @MigrateSource(
 *   id = "transaction"
 * )
 */
class Transaction extends SqlBase {

  protected static $paymentMethodsMapping = [
    'card' => 'credit_card',
    'paypal' => 'paypal',
  ];

  protected static $statusesMapping = [
    'none' => TransactionEntity::STATUS_FAILED,
    'new' => TransactionEntity::STATUS_PENDING,
    'authorized' => TransactionEntity::STATUS_SUCCESS,
    'completed' => TransactionEntity::STATUS_SUCCESS,
    'pending' => TransactionEntity::STATUS_PENDING,
    'refunded' => TransactionEntity::STATUS_REFUNDED,
    'rejected' => TransactionEntity::STATUS_FAILED,
    'voided' => TransactionEntity::STATUS_FAILED,
    'expired' => TransactionEntity::STATUS_FAILED,
    'reversed' => TransactionEntity::STATUS_FAILED,
    'unknown' => TransactionEntity::STATUS_FAILED,
  ];

  protected static $ackStatusesMapping = [
    'Success' => PaypalAPI::ACK_SUCCESS,
    'SuccessWithWarning' => PaypalAPI::ACK_SUCCESS_WITH_WARNING,
    'PartialSuccess' => PaypalAPI::ACK_SUCCESS,
    'Failure' => PaypalAPI::ACK_FAILURE,
    'FailureWithWarning' => PaypalAPI::ACK_FAILURE_WITH_WARNING,
    'Warning' => PaypalAPI::ACK_FAILURE_WITH_WARNING,
  ];

  protected static $transactionTypeMapping = [
    'payment' => TransactionEntity::TYPE_PAYMENT,
    'refund' => TransactionEntity::TYPE_REFUND,
  ];

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_payment_paypal_transaction', 'yppt');
    $parentCondition = $query->orConditionGroup()
      ->condition('yppt.parent_transaction_id', '')
      ->isNull('yppt.parent_transaction_id');
//    $continueMigrationCondition = $query->orConditionGroup()
//      ->condition('ypt.created_at', '2017-07-10 04:10:20', '>')
//      ->condition('ypt.updated_at', '2017-07-10 04:10:20', '>');
    $query->fields('yppt', ['id', 'payment_transaction_id', 'ack', 'created_at', 'updated_at']);
    $query->fields('ypt', ['type', 'status', 'amount', 'currency', 'payment_amount', 'payment_currency',
      'currency_rate', 'ip_address', 'user_id', 'merchant_id', 'payment_by', 'error_messages']);
    $query->addField('yoitr', 'invoice_id', 'invoice_id');
    $query->fields('sgu', ['email_address']);
    $query->join('ya_payment_transaction', 'ypt', 'ypt.transaction_id=yppt.payment_transaction_id');
    $query->join('ya_order_invoice_transaction_ref', 'yoitr', 'yoitr.transaction_id=ypt.transaction_id');
    $query->join('ya_order_invoice', 'yoi', 'yoi.id=yoitr.invoice_id');
    $query->join('ya_order', 'yo', 'yo.id=yoi.order_id');
    $query->join('sf_guard_user', 'sgu', 'sgu.id=yo.user_id');
    $query->condition('ypt.merchant_id', ['PaypalRT', 'PaypalAU'], 'IN');
    $query->condition($parentCondition);
    $query->condition('ypt.type', ['payment', 'refund'], 'IN');
    $query->condition('yo.type', 1);
    $query->condition('yo.site', 'RT');
//    $query->condition($continueMigrationCondition);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('ID'),
      'payment_transaction_id' => $this->t('Remote transaction ID'),
      'ack' => $this->t('Payment system status'),
      'created_at' => $this->t('Created'),
      'updated_at' => $this->t('Changed'),
      'status' => $this->t('Status'),
      'amount' => $this->t('Amount'),
      'currency' => $this->t('Currency'),
      'payment_amount' => $this->t('Payment amount'),
      'payment_currency' => $this->t('Payment currency'),
      'currency_rate' => $this->t('Currency rate'),
      'ip_address' => $this->t('IP address'),
      'user_id' => $this->t('User ID'),
      'invoice_id' => $this->t('Invoice ID'),
      'error_messages' => $this->t('Message'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'payment_transaction_id' => [
        'type' => 'string',
        'alias' => 'yppt',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Prepare status field value.
    $status = $row->getSourceProperty('status');
    $row->setSourceProperty('status', static::$statusesMapping[$status]);

    // Prepare remote_status field value.
    $remoteStatus = $row->getSourceProperty('ack');
    $row->setSourceProperty('remote_status', static::$ackStatusesMapping[$remoteStatus]);

    // Prepare payment_method field value.
    if ($row->getSourceProperty('payment_by')) {
      $paymentMethod = static::$paymentMethodsMapping[$row->getSourceProperty('payment_by')];
    }
    else {
      $paymentMethod = static::$paymentMethodsMapping['card'];
    }
    $row->setSourceProperty('payment_method', $paymentMethod);

    // Prepare transaction type field value.
    $transactionType = static::$transactionTypeMapping[$row->getSourceProperty('type')];
    $row->setSourceProperty('transaction_type', $transactionType);

    // Prepare merchant_id field value.
    /** @var  $merchantStorage \Drupal\Core\Entity\EntityStorageInterface */
    $merchantStorage =  $query = \Drupal::service('entity_type.manager')->getStorage('merchant');
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $merchantStorage->getQuery();
    $query->condition('merchant_id', $row->getSourceProperty('merchant_id'));
    $ids = $query->execute();
    if ($ids) {
      $merchantHasFound = false;
      $merchants = $merchantStorage->loadMultiple($ids);
      /** @var \Drupal\payment\Entity\Merchant $merchant */
      foreach ($merchants as $merchant) {
        if (in_array($paymentMethod, $merchant->getPaymentMethods())) {
          $merchantHasFound = true;
          $row->setSourceProperty('merchant_entity_id', $merchant->id());
          break;
        }
      }
    }
    else {
      return false;
    }

    // Break importing the row, if merchant didn't find.
    if (!$merchantHasFound) {
      return false;
    }

    /** @var \Drupal\user\Entity\User $user */
    if ($user = user_load_by_mail($row->getSourceProperty('email_address'))) {
      $row->setSourceProperty('destination_user_id', $user->id());
    }
    else {
      $query = \Drupal::database()->select('migrate_map_user', 'mmu');
      $query->addField('mmu', 'destid1', 'user_id');
      $query->condition('mmu.sourceid1', $row->getSourceProperty('user_id'));
      $row->setSourceProperty('destination_user_id', $query->execute()->fetchField());
    }

    return parent::prepareRow($row);
  }
}