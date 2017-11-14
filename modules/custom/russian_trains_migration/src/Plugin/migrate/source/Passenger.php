<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the Passenger.
 *
 * @MigrateSource(
 *   id = "passenger"
 * )
 */
class Passenger extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_train_order_passenger', 'ytop');
//    $continueMigrationCondition = $query->orConditionGroup()
//      ->condition('yo.created_at', '2017-07-10 04:10:20', '>')
//      ->condition('yo.updated_at', '2017-07-10 04:10:20', '>');
    $query->fields('ytop', ['id', 'first_name', 'last_name', 'gender', 'id_number', 'citizenship', 'date_of_birth']);
    $query->fields('yo', ['user_id']);
    $query->fields('sgu', ['email_address']);
    $query->join('ya_train_order_ticket', 'ytot', 'ytot.id=ytop.ticket_id');
    $query->join('ya_order', 'yo', 'yo.id=ytot.order_id');
    $query->join('sf_guard_user', 'sgu', 'sgu.id=yo.user_id');
//    $query->condition($continueMigrationCondition);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Product id'),
      'first_name' => $this->t('First name'),
      'last_name' => $this->t('Last name'),
      'gender' => $this->t('Gender'),
      'id_number' => $this->t('ID number'),
      'citizenship' => $this->t('Citizenship'),
      'date_of_birth' => $this->t('Date of birth'),
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
        'alias' => 'ytop',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($row->getSourceProperty('gender') == 'M') {
      $row->setSourceProperty('gender', 'male');
    }
    elseif ($row->getSourceProperty('gender') == 'F') {
      $row->setSourceProperty('gender', 'female');
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