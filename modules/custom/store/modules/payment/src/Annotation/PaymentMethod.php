<?php

namespace Drupal\payment\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Payment method item annotation object.
 *
 * @see \Drupal\payment\Plugin\PaymentMethodManager
 * @see plugin_api
 *
 * @Annotation
 */
class PaymentMethod extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * Operations provider class name.
   *
   * @var string
   */
  public $operations_provider;

}
