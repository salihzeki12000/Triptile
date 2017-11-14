<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

abstract class SalesforceMappingObject extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Salesforce mapping object ID'),
      'sforce_id' => $this->t('Sforce ID'),
      'sforce_type' => $this->t('Sforce type'),
      'updated_at' => $this->t('Changed'),
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
        'alias' => 'ysb',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Prepare changed timestamp.
    $changed = new DrupalDateTime($row->getSourceProperty('updated_at'));
    $row->setSourceProperty('changed', $changed->getTimestamp());

    return parent::prepareRow($row);
  }
}