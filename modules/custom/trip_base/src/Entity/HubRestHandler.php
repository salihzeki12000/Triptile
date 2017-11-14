<?php

namespace Drupal\trip_base\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\master\Entity\EntityRestHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class HubRestHandler extends EntityRestHandler {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * HubRestHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory, CountryManagerInterface $country_manager) {
    parent::__construct($entity_type, $entity_type_manager);

    $this->entityQuery = $query_factory;
    $this->countryManager = $country_manager;
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
   * @param \Drupal\trip_base\Entity\Hub $entity
   */
  public function prepareResponse(EntityInterface $entity) {
    $data = [
      'id' => $entity->id(),
      'name' => $entity->getName(),
      'description' => $entity->getDescription(),
      'latitude' => $entity->getLatitude(),
      'longitude' => $entity->getLongitude(),
      'rating' => $entity->getRating(),
      'countryCode' => $entity->getCountry(),
      'countryName' => isset($this->countryManager->getList()[$entity->getCountry()]) ? $this->countryManager->getList()[$entity->getCountry()] : null,
      'regionKey' => $entity->getRegion(),
      'regionName' => isset($this->countryManager->getRegions()[$entity->getRegion()]) ? $this->countryManager->getRegions()[$entity->getRegion()] : null,
      'recommendedDays' => $entity->getRecommendedNumberOfDays(),
      'startPoint' => $entity->isStartPoint(),
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
      'latitude' => 'geolocation.lat',
      'longitude' => 'geolocation.lng',
      'rating' => 'rating',
      'countryCode' => 'country',
      'regionKey' => 'region',
      'recommendedDays' => 'days',
      'startPoint' => 'start_point',
    ];
    foreach ($fields as $apiField => $entityField) {
      $value = $request->query->get($apiField);
      if ($value !== NULL) {
        $op = $request->query->get($apiField . 'Op') ?: NULL;
        $query->condition($entityField, $value, $op);
      }
    }

    $ids = $query->execute();
    return $this->loadEntities($ids);
  }

}
