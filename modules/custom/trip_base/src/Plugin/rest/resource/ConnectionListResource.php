<?php

namespace Drupal\trip_base\Plugin\rest\resource;

use Drupal\master\Plugin\rest\resource\EntityListResource;

/**
 * Represents hub list as resource.
 *
 * @RestResource(
 *   id = "connection_list",
 *   label = @Translation("List of connections"),
 *   uri_paths = {
 *     "canonical" = "/connection",
 *     "https://www.drupal.org/link-relations/create" = "/connection"
 *   }
 * )
 */
class ConnectionListResource extends EntityListResource {

  /**
   * {@inheritdoc}
   */
  static protected $entityTypeId = 'connection';

}
