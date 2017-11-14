<?php

namespace Drupal\payment\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Payment adapter item annotation object.
 *
 * @see \Drupal\payment\Plugin\PaymentAdapterManager
 * @see plugin_api
 *
 * @Annotation
 */
class PaymentAdapter extends Plugin {


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
   * @var string
   */
  public $payment_system;

}
