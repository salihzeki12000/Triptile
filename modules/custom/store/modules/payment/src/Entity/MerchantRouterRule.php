<?php

namespace Drupal\payment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * // TODO replace with rules when it gets stable.
 *
 * Defines the Merchant router rule entity.
 *
 * @ConfigEntityType(
 *   id = "merchant_router_rule",
 *   label = @Translation("Merchant router rule"),
 *   handlers = {
 *     "list_builder" = "Drupal\payment\MerchantRouterRuleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\payment\Form\Admin\MerchantRouterRuleForm",
 *       "edit" = "Drupal\payment\Form\Admin\MerchantRouterRuleForm",
 *       "delete" = "Drupal\payment\Form\Admin\MerchantRouterRuleDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "merchant_router_rule",
 *   admin_permission = "payment.administer:",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/store/payment/merchant-router-rule/{merchant_router_rule}",
 *     "add-form" = "/admin/store/payment/merchant-router-rule/add",
 *     "edit-form" = "/admin/store/payment/merchant-router-rule/{merchant_router_rule}/edit",
 *     "delete-form" = "/admin/store/payment/merchant-router-rule/{merchant_router_rule}/delete",
 *     "collection" = "/admin/store/payment/merchant-router-rule"
 *   }
 * )
 */
class MerchantRouterRule extends ConfigEntityBase implements MerchantRouterRuleInterface {

  /**
   * The Merchant router rule ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Merchant router rule label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Merchant router rule weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * The Merchant router rule executable condition.
   *
   * @var string
   */
  protected $condition;

  /**
   * The list of merchant that can be used to process the payment.
   *
   * @var array
   */
  protected $merchants = [];

  /**
   * @inheritdoc
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * @inheritdoc
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * @inheritdoc
   */
  public function isApplicable($vars) {
    return \Drupal::service('master.expression_language')->evaluate($this->condition, $vars);
  }

  /**
   * @inheritdoc
   */
  public function setCondition($condition) {
    $this->condition = $condition;
    return $this;
  }

  /**
   * @inheritdoc
   */
  public function getCondition() {
    return $this->condition;
  }

  /**
   * @inheritdoc
   */
  public function getMerchantIds() {
    return $this->merchants;
  }

  /**
   * @inheritdoc
   */
  public function setMerchantIds(array $merchant_ids) {
    $this->merchants = $merchant_ids;
    return $this;
  }

  /**
   * @inheritdoc
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    if (!empty($this->getMerchantIds())) {
      foreach ($this->entityTypeManager()->getStorage('merchant')->loadMultiple($this->getMerchantIds()) as $merchant) {
        $this->addDependency('config', $merchant->getConfigDependencyName());
      }
    }

    return $this;
  }

}
