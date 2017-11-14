<?php

namespace Drupal\store\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Order item type entity.
 *
 * @ConfigEntityType(
 *   id = "order_item_type",
 *   label = @Translation("Order item type"),
 *   handlers = {
 *     "list_builder" = "Drupal\store\OrderItemTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\store\Form\OrderItemTypeForm",
 *       "edit" = "Drupal\store\Form\OrderItemTypeForm",
 *       "delete" = "Drupal\store\Form\OrderItemTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "order_item_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "order_item",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/store/order-item-type/{order_item_type}",
 *     "add-form" = "/admin/store/order-item-type/add",
 *     "edit-form" = "/admin/store/order-item-type/{order_item_type}/edit",
 *     "delete-form" = "/admin/store/order-item-type/{order_item_type}/delete",
 *     "collection" = "/admin/store/order-item-type"
 *   },
 *   settings_form = "Drupal\store\Form\OrderItemTypeSettingsForm"
 * )
 */
class OrderItemType extends ConfigEntityBundleBase implements OrderItemTypeInterface {

  /**
   * The Order item type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Order item type label.
   *
   * @var string
   */
  protected $label;

}
