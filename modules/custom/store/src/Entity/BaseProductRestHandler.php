<?php
namespace Drupal\store\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\master\Entity\EntityRestHandler;
use Drupal\store\DefaultCurrency;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BaseProductRestHandler extends EntityRestHandler {

  /**
   * @var \Drupal\store\DefaultCurrency
   */
  protected $defaultCurrency;

  /**
   * BaseProductRestHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\store\DefaultCurrency $default_currency
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, DefaultCurrency $default_currency) {
    parent::__construct($entity_type, $entity_type_manager);

    $this->defaultCurrency = $default_currency;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('store.default_currency')
    );
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\store\Entity\BaseProduct $entity
   */
  public function prepareResponse(EntityInterface $entity) {
    $userPrice = $entity->getPrice()->convert($this->defaultCurrency->getUserCurrency());
    $data = [
      'id' => $entity->id(),
      'name' => $entity->getName(),
      'description' => $entity->getDescription(),
      'price' => $userPrice->getNumber(),
      'currency' => $userPrice->getCurrencyCode(),
      'weight' => $entity->getWeight(),
      'isDefault' => $entity->isDefault(),
      'availableForm' => $entity->getAvailableFrom()->format('Y-m-d'),
      'availableUntil' => $entity->getAvailableUntil()->format('Y-m-d'),
    ];

    return $data;
  }

}
