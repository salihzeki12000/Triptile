<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the CustomerProfile.
 *
 * @MigrateSource(
 *   id = "customer_profile"
 * )
 */
class CustomerProfile extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_payment_billing_address', 'ypba');
//    $continueMigrationCondition = $query->orConditionGroup()
//      ->condition('yoi.created_at', '2017-07-10 04:10:20', '>')
//      ->condition('yoi.updated_at', '2017-07-10 04:10:20', '>');
    $query->fields('ypba', ['id', 'first_name', 'last_name', 'middle_name', 'street1',  'street2', 'city',
      'state', 'country', 'postal_code', 'phone', 'email']);
    $query->fields('yoitr', ['invoice_id']);
    $query->fields('ypt', ['user_id']);
    $query->fields('sgu', ['email_address']);
    $query->join('ya_payment_transaction', 'ypt', 'ypt.transaction_id=ypba.transaction_id');
    $query->join('ya_order_invoice_transaction_ref', 'yoitr', 'yoitr.transaction_id=ypt.transaction_id');
    $query->join('ya_order_invoice', 'yoi', 'yoi.id=yoitr.invoice_id');
    $query->join('ya_order', 'yo', 'yo.id=yoi.order_id');
    $query->join('sf_guard_user', 'sgu', 'sgu.id=yo.user_id');
    $query->isNotNull('ypba.country');
    $query->condition('ypba.country', '', '!=');
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
      'id' => $this->t('User id'),
      'email' => $this->t('Email'),
      'is_active' => $this->t('Status'),
      'first_name' => $this->t('First name'),
      'last_name' => $this->t('Last name'),
      'middle_name' => $this->t('Middle name'),
      'street1' => $this->t('Street address'),
      'street2' => $this->t('Street address line 2'),
      'city' => $this->t('City'),
      'state' => $this->t('State'),
      'country' => $this->t('Country'),
      'postal_code' => $this->t('Postal code'),
      'phone' => $this->t('Phone number'),
      'invoice_id' => $this->t('Phone number'),
      'user_id' => $this->t('Phone number'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'ypba',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
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