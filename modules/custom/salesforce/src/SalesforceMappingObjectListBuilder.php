<?php

namespace Drupal\salesforce;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Salesforce mapping object entities.
 *
 * @ingroup salesforce
 */
class SalesforceMappingObjectListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['entity'] = $this->t('Mapped entity');
    $header['record'] = $this->t('Salesforce record');
    $header['next_action'] = $this->t('Next action');
    $header['count_of_tries'] = $this->t('Count of tries');
    $header['last_action'] = $this->t('Last action');
    $header['last_sync_time'] = $this->t('Last sync time');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\salesforce\Entity\SalesforceMappingObject */
    $row['entity'] = $entity->getMappedEntityId() . ' ' . $entity->getMappedEntityTypeId();
    $row['record'] = $entity->getRecordId() . ' ' . $entity->getSalesforceObject();
    $row['next_action'] = $entity->getNextSyncAction();
    $row['count_of_tries'] = $entity->getTries();
    $row['last_action'] = $entity->getLastSyncAction();
    $row['last_sync_time'] = date('Y-m-d H:i:s', $entity->getLastSyncTime());
    return $row + parent::buildRow($entity);
  }

}
