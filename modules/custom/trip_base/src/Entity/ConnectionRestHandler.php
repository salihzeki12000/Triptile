<?php
namespace Drupal\trip_base\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\master\Entity\EntityRestHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ConnectionRestHandler extends EntityRestHandler {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * ConnectionRestHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory) {
    parent::__construct($entity_type, $entity_type_manager);

    $this->entityQuery = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('entity.query'),
      $container->get('country_manager')
    );
  }


  /**
   * {@inheritdoc}
   * @param \Drupal\trip_base\Entity\Connection $entity
   */
  public function prepareResponse(EntityInterface $entity) {
    $priceOptionsHandler = $this->entityTypeManager->getHandler('base_product', 'rest');
    $priceOptionsData = [];
    foreach ($entity->getPriceOptions() as $priceOption) {
      $priceOptionsData[] = $priceOptionsHandler->prepareResponse($priceOption);
    }

    $data = [
      'id' => $entity->id(),
      'name' => $entity->getName(),
      'description' => $entity->getDescription(),
      'pointA' => $entity->getPointA(true),
      'pointB' => $entity->getPointB(true),
      'typeKey' => $entity->getType(),
      'typeName' => $entity::getTypeOptions()[$entity->getType()],
      'tripDuration' => $entity->getDuration(),
      'rating' => $entity->getRating(),
      'overallRating' => $entity->getOverallRating(),
      'priceOptions' => $priceOptionsData,
    ];

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntities(Request $request) {
    $query = $this->entityQuery->get($this->entityType->id());
    $fields = [
      'id' => 'id',
      'name' => 'name',
      'pointA' => 'point_1',
      'pointB' => 'point_2',
      'point' => 'point',
      'rating' => 'rating',
      'typeKey' => 'type',
      'tripDuration' => 'duration',
      'overallRating' => 'overall_rating',
    ];
    foreach ($fields as $apiField => $entityField) {
      $value = $request->query->get($apiField);
      if ($value !== NULL) {
        $op = $request->query->get($apiField . 'Op') ?: NULL;
        if ($apiField == 'point') {
          $or = $query->orConditionGroup();
          $or->condition('point_1', $value, $op);
          $or->condition('point_2', $value, $op);
          $query->condition($or);
        }
        else {
          $query->condition($entityField, $value, $op);
        }
      }
    }

    $ids = $query->execute();
    return $this->loadEntities($ids);
  }

}
