<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the User.
 *
 * @MigrateSource(
 *   id = "user"
 * )
 */
class User extends SqlBase {
  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('sf_guard_user', 'sgu');
//    $continueMigrationCondition = $query->orConditionGroup()
//      ->condition('sgu.created_at', '2017-07-10 04:10:20', '>')
//      ->condition('sgu.updated_at', '2017-07-10 04:10:20', '>');
    $query->fields('sgu', ['id', 'email_address', 'username', 'salt',  'password', 'is_active']);
    $query->fields('yap', ['first_name', 'last_name', 'middle_name', 'street1',  'street2', 'city',
      'state', 'country', 'postal_code', 'phone']);
    $query->leftJoin('ya_account_profile', 'yap', 'sgu.id=yap.user_id');
//    $query->condition($continueMigrationCondition);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('User id'),
      'email_address' => $this->t('Email'),
      'username' => $this->t('Username'),
      'salt' => $this->t('Salt'),
      'password' => $this->t('Password'),
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
        'alias' => 'sgu',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (user_load_by_mail($row->getSourceProperty('email_address'))) {
      return false;
    }
    $pass = '#S#';
    $pass .= $row->getSourceProperty('salt');
    $pass .= '#';
    $pass .= $row->getSourceProperty('password');
    $row->setSourceProperty('pass', $pass);
    return parent::prepareRow($row);
  }
}