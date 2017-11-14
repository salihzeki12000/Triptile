<?php

namespace Drupal\master\Plugin\rest\resource;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\Plugin\rest\resource\EntityResource as EntityResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Represents Hub entities as resource.
 *
 */
class EntityResource extends EntityResourceBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, array $serializer_formats, LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $serializer_formats, $logger, $config_factory);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function get(EntityInterface $entity) {
    try {
      $handler = $this->entityTypeManager->getHandler($this->entityType->id(), 'rest');
      $entity_access = $entity->access('view', NULL, TRUE);
      if (!$entity_access->isAllowed()) {
        throw new AccessDeniedHttpException();
      }

      $data = $handler->prepareResponse($entity);
      $response = new ResourceResponse($data, 200);
      $response->addCacheableDependency($entity);
      $response->addCacheableDependency($entity_access);

      return $response;
    }
    catch (InvalidPluginDefinitionException $exception) {
      return parent::get($entity);
    }
  }

}