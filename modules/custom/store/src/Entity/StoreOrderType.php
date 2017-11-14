<?php

namespace Drupal\store\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Store order type entity.
 *
 * @ConfigEntityType(
 *   id = "store_order_type",
 *   label = @Translation("Store order type"),
 *   handlers = {
 *     "list_builder" = "Drupal\store\StoreOrderTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\store\Form\StoreOrderTypeForm",
 *       "edit" = "Drupal\store\Form\StoreOrderTypeForm",
 *       "delete" = "Drupal\store\Form\StoreOrderTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "store_order_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "store_order",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/store/store-order-type/{store_order_type}",
 *     "add-form" = "/admin/store/store-order-type/add",
 *     "edit-form" = "/admin/store/store-order-type/{store_order_type}/edit",
 *     "delete-form" = "/admin/store/store-order-type/{store_order_type}/delete",
 *     "collection" = "/admin/store/store-order-type"
 *   },
 *   settings_form = "Drupal\store\Form\StoreOrderTypeSettingsForm"
 * )
 */
class StoreOrderType extends ConfigEntityBundleBase implements StoreOrderTypeInterface {

  /**
   * The Store order type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Store order type label.
   *
   * @var string
   */
  protected $label;

}
