<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Source plugin for the SalesforceMappingObjectUser.
 *
 * @MigrateSource(
 *   id = "salesforce_mapping_object_user"
 * )
 */
class SalesforceMappingObjectUser extends SalesforceMappingObject {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('ya_sforce_binding', 'ysb');
    $query->fields('ysb', ['id', 'sforce_id', 'sforce_type', 'model_class', 'model_key', 'key_value', 'updated_at']);
    $query->addField('sgu', 'id', 'user_id');
    $query->addField('sgu', 'email_address', 'email_address');
    $query->join('sf_guard_user', 'sgu', 'sgu.email_address=ysb.key_value');
    $query->condition('ysb.model_class', 'sfGuardUser');

    return $query;
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