<?php

/**
 * @file
 * Contains \Drupal\rn_user\Routing\RouteSubscriber.
 */

namespace Drupal\rn_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route_item = $collection->get('entity.user.canonical')) {
      $route_item->setDefault('_title', 'Overview');
      $route_item->setDefault('_title_callback', '');
    }
  }

}
