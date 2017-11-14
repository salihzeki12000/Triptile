<?php

namespace Drupal\payment;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Merchant router rule entities.
 */
class MerchantRouterRuleListBuilder extends ConfigEntityListBuilder {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MerchantRouterRuleListBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $storage);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['weight'] = $this->t('Weight');
    $header['condition'] = $this->t('Condition');
    $header['merchants'] = $this->t('Merchants');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\payment\Entity\MerchantRouterRule $entity
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['weight'] = $entity->getWeight();
    $row['condition'] = $entity->getCondition();
    $merchantNames = [];
    foreach ($this->entityTypeManager->getStorage('merchant')->loadMultiple($entity->getMerchantIds()) as $merchant) {
      $merchantNames[] = $merchant->label();
    }
    $row['merchants'] = implode(', ', $merchantNames);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    return $this->getStorage()
      ->getQuery()
      ->sort('weight')
      ->execute();
  }

}
