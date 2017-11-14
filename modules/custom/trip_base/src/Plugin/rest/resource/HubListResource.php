<?php

namespace Drupal\trip_base\Plugin\rest\resource;

use Drupal\master\Plugin\rest\resource\EntityListResource;

/**
 * Represents hub list as resource.
 *
 * @RestResource(
 *   id = "hub_list",
 *   label = @Translation("List of hubs"),
 *   uri_paths = {
 *     "canonical" = "/hub",
 *     "https://www.drupal.org/link-relations/create" = "/hub"
 *   }
 * )
 */
class HubListResource extends EntityListResource {

  /**
   * {@inheritdoc}
   */
  static protected $entityTypeId = 'hub';

}
