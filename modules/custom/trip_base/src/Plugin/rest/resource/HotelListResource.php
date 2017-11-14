<?php

namespace Drupal\trip_base\Plugin\rest\resource;

use Drupal\master\Plugin\rest\resource\EntityListResource;

/**
 * Represents hotel list as resource.
 *
 * @RestResource(
 *   id = "hotel_list",
 *   label = @Translation("List of hotels"),
 *   uri_paths = {
 *     "canonical" = "/hotel",
 *     "https://www.drupal.org/link-relations/create" = "/hotel"
 *   }
 * )
 */
class HotelListResource extends EntityListResource {

  /**
   * {@inheritdoc}
   */
  static protected $entityTypeId = 'hotel';

}
