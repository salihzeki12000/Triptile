<?php

namespace Drupal\store\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\store\Entity\Invoice;
use Drupal\store\Entity\StoreOrder;
use Symfony\Component\Routing\Route;

class StoreParamConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    switch ($definition['type']) {
      case 'store_order':
        if (is_numeric($value)) {
          return StoreOrder::load($value);
        }
        else {
          $orders = \Drupal::entityTypeManager()
            ->getStorage('store_order')
            ->loadByProperties(['hash' => $value]);
          return reset($orders);
        }


      case 'invoice':
        if (is_numeric($value)) {
          return Invoice::load($value);
        }
        else {
          $invoices = \Drupal::entityTypeManager()
            ->getStorage('invoice')
            ->loadByProperties(['number' => $value]);
          return reset($invoices);
        }

      default:
        return null;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && in_array($definition['type'], ['store_order', 'invoice']);
  }

}
