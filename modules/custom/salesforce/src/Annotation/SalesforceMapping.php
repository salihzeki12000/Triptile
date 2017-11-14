<?php

namespace Drupal\salesforce\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Salesforce mapping item annotation object.
 *
 * @see \Drupal\salesforce\Plugin\SalesforceMappingManager
 * @see plugin_api
 *
 * @Annotation
 */
class SalesforceMapping extends Plugin {


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
   * Mapped entity type id.
   *
   * @var string
   */
  public $entity_type_id;

  /**
   * Operations that will trigger export to sf. Available options are 'create',
   * 'update', 'delete'.
   *
   * @var array
   */
  public $entity_operations;

  /**
   * API name of the mapped object.
   *
   * @var string
   */
  public $salesforce_object;

  /**
   * Operations that will trigger import from sf. Available options are 'create',
   * 'update', 'delete'.
   *
   * @var array
   */
  public $object_operations;

  /**
   * Defines which source has priority in case of a conflict.
   *
   * @var string
   */
  public $priority;

}
