<?php

namespace Drupal\master\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Field form type item annotation object.
 *
 * @see \Drupal\master\FieldFormTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class FieldFormType extends Plugin {


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

}
