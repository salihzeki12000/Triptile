<?php

/**
 * @file
 * Contains \Drupal\store\Annotation\TrainProvider.
 */

namespace Drupal\store\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a TrainProvider item annotation object.
 *
 * Plugin Namespace: Plugin\store\TrainProvider
 *
 * @see \Drupal\store\Plugin\OrderRendererManager
 * @see plugin_api
 *
 * @Annotation
 */
class OrderRenderer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the TrainProvider.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * Type of order.
   *
   * @var string
   */
  public $order_type;
}
