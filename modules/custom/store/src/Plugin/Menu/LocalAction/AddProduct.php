<?php

namespace Drupal\store\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

class AddProduct extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $parameters = parent::getRouteParameters($route_match);
    $parameters['base_product_type'] = $this->pluginDefinition['product_type'];
    return $parameters;
  }

}
