<?php

namespace Drupal\trip_base\Plugin\rest\resource;

use Drupal\master\Plugin\rest\resource\EntityListResource;

/**
 * Represents transfer list as resource.
 *
 * @RestResource(
 *   id = "transfer_list",
 *   label = @Translation("List of transfers"),
 *   uri_paths = {
 *     "canonical" = "/transfer",
 *     "https://www.drupal.org/link-relations/create" = "/transfer"
 *   }
 * )
 */
class TransferListResource extends EntityListResource {

  /**
   * {@inheritdoc}
   */
  static protected $entityTypeId = 'transfer';

}
