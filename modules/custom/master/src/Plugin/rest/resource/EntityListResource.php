<?php

namespace Drupal\master\Plugin\rest\resource;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class that has to be used to present list of entities.
 */
abstract class EntityListResource extends ResourceBase {

  /**
   * Entity type id.
   *
   * @var null
   */
  static protected $entityTypeId = null;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * HubListResource constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param array $serializer_formats
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('trip_base'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Handles GET request. Returns response with list of entities.
   *
   * @param $unserialized
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($unserialized, Request $request) {
    $data = [];
    $accesses = [];
    $entities = $this->getEntities($request);

    try {
      $handler = $this->getEntityRestHandler();
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      foreach ($entities as $entity) {
        $entity_access = $entity->access('view', NULL, TRUE);
        if ($entity_access->isAllowed()) {
          $data[] = $handler->prepareResponse($entity);
          $accesses[$entity->id()] = $entity_access;
        }
      }
    }
    catch (InvalidPluginDefinitionException $exception) {}

    // @todo Make cache smarter.
    $response = new ResourceResponse($data, 200);
    foreach ($entities as $entity) {
      if (isset($accesses[$entity->id()])) {
        $response->addCacheableDependency($accesses[$entity->id()]);
      }
    }
    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->setCacheTags($this->getEntityType()->getListCacheTags());
    $cacheableMetadata->setCacheContexts(array_merge($this->getEntityType()->getListCacheContexts(), ['url.query_args']));
    $response->addCacheableDependency($cacheableMetadata);

    return $response;
  }

  /**
   * Gets list of entities.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  protected function getEntities(Request $request) {
    return $this->getEntityRestHandler()->getEntities($request);
  }

  /**
   * Gets the entity type object.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   */
  protected function getEntityType() {
    return $this->entityTypeManager->getDefinition(static::$entityTypeId);
  }

  /**
   * Gets the entity rest handler instance;
   *
   * @return \Drupal\master\Entity\EntityRestHandlerInterface
   */
  protected function getEntityRestHandler() {
    return $this->entityTypeManager->getHandler(static::$entityTypeId, 'rest');
  }

}
