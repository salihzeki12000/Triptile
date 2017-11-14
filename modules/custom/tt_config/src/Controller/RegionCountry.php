<?php

namespace Drupal\tt_config\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class RegionCountry extends ControllerBase{

  /*
   * Callback for Region-Country get mapping json
   */
  public function get_json() {
    $config = \Drupal::config('tt_config.config');
    return new JsonResponse($config->get());
  }
}