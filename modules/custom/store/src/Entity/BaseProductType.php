<?php

namespace Drupal\store\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Base product type entity.
 *
 * @ConfigEntityType(
 *   id = "base_product_type",
 *   label = @Translation("Base product type"),
 *   handlers = {
 *     "list_builder" = "Drupal\store\BaseProductTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\store\Form\BaseProductTypeForm",
 *       "edit" = "Drupal\store\Form\BaseProductTypeForm",
 *       "delete" = "Drupal\store\Form\BaseProductTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "base_product_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "base_product",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/store/base-product-type/{base_product_type}",
 *     "add-form" = "/admin/store/base-product-type/add",
 *     "edit-form" = "/admin/store/base-product-type/{base_product_type}/edit",
 *     "delete-form" = "/admin/store/base-product-type/{base_product_type}/delete",
 *     "collection" = "/admin/store/base-product-type"
 *   },
 *   settings_form = "Drupal\store\Form\BaseProductTypeSettingsForm"
 * )
 */
class BaseProductType extends ConfigEntityBundleBase implements BaseProductTypeInterface {

  /**
   * The Base product type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Base product type label.
   *
   * @var string
   */
  protected $label;

}
