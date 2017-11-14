<?php

namespace Drupal\tt_config\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class Recommended extends ControllerBase{

  /*
   * Callback for recommended json
   */
  public function get_json($point_1) {
    $query = \Drupal::database()->select('connection_field_data', 'c');
    $query->fields('c');
    $query->condition('c.point_1', $point_1);
    $connections = $query->execute()->fetchAllAssoc('id');

    $hubsId = [];
    foreach($connections as $key => $connection){
      if(!in_array($connection->point_2, $hubsId)){
        $hubsId[] = $connection->point_2;
      }
    }

    if(sizeof($hubsId > 0)){

    }

    return new JsonResponse($hubsId);
  }
}