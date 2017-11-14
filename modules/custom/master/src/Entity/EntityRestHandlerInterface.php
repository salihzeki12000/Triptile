<?php

namespace Drupal\master\Entity;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;

interface EntityRestHandlerInterface {

  /**
   * Converts an entity to array that will be sent as response of an entity rest
   * resource.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @return array
   */
  public function prepareResponse(EntityInterface $entity);

  /**
   * Gets list of entities using request params from the request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  public function getEntities(Request $request);

}
