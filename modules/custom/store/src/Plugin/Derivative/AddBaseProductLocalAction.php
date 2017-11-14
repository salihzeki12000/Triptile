<?php

namespace Drupal\store\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\views\Views;

class AddBaseProductLocalAction extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if ($view = Views::getView('products_admin')) {
      // @todo make it smarter - go over displays and find pages.
      $route_name = 'entity.base_product.add_form';
      $type = 'ticket_product';
      $key = $route_name . '.' . $type;
      $this->derivatives[$key] = $base_plugin_definition;
      $this->derivatives[$key]['route_name'] = 'entity.base_product.add_form';
      $this->derivatives[$key]['class'] = '\Drupal\store\Plugin\Menu\LocalAction\AddProduct';
      $this->derivatives[$key]['product_type'] = $type;
      $this->derivatives[$key]['title'] = t('Add Ticket Product');
      $view = Views::getView('products_admin');
      $view->initDisplay();
      $this->derivatives[$key]['appears_on'][] = $view->displayHandlers->get('page_2')->getRouteName();
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
