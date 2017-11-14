<?php

/**
 * @file
 * Contains \Drupal\train_provider\Annotation\TrainProvider.
 */

namespace Drupal\train_provider\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a TrainProvider item annotation object.
 *
 * Plugin Namespace: Plugin\train_provider\TrainProvider
 *
 * @see \Drupal\train_provider\Plugin\train_providerManager
 * @see plugin_api
 *
 * @Annotation
 */
class TrainProvider extends Plugin {

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
   * Operations provider class name.
   *
   * @var string
   */
  public $operations_provider;

  /**
   * Description of the train provider.
   *
   * @var string
   */
  public $description;

  /**
   * The indicator, which shows can we use this provider as price updater.
   *
   * @var bool
   */
  public $price_updater;
}
