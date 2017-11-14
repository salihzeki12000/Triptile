<?php

namespace Drupal\trip_base\Plugin\rest\resource;

use Drupal\master\Plugin\rest\resource\EntityListResource;

/**
 * Represents activity list as resource.
 *
 * @RestResource(
 *   id = "activity_list",
 *   label = @Translation("List of activities"),
 *   uri_paths = {
 *     "canonical" = "/activity",
 *     "https://www.drupal.org/link-relations/create" = "/activity"
 *   }
 * )
 */
class ActivityListResource extends EntityListResource {

  /**
   * {@inheritdoc}
   */
  static protected $entityTypeId = 'activity';

}
