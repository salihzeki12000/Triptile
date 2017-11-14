<?php

namespace Drupal\store\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the price rule entity.
 *
 * @ConfigEntityType(
 *   id = "price_rule",
 *   label = @Translation("Price rule"),
 *   handlers = {
 *     "list_builder" = "Drupal\store\PriceRuleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\store\Form\Admin\PriceRuleForm",
 *       "edit" = "Drupal\store\Form\Admin\PriceRuleForm",
 *       "delete" = "Drupal\store\Form\Admin\PriceRuleDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "price_rule",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/store/price-rule/{price_rule}",
 *     "add-form" = "/admin/store/price-rule/add",
 *     "edit-form" = "/admin/store/price-rule/{price_rule}/edit",
 *     "delete-form" = "/admin/store/price-rule/{price_rule}/delete",
 *     "collection" = "/admin/store/price-rule"
 *   }
 * )
 */
class PriceRule extends ConfigEntityBase implements PriceRuleInterface {

  /**
   * The Price rule ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Price rule label.
   *
   * @var string
   */
  protected $label;

  /**
   * The type of entity is requesting Price rule.
   *
   * @var string
   */
  protected $price_rule_type;

  /**
   * The Price rule condition.
   *
   * @var string
   */
  protected $condition;

  /**
   * The Price rule tax type.
   *
   * @var string
   */
  protected $tax_type;

  /**
   * The Price rule tax value.
   *
   * @var float
   */
  protected $tax_value;

  /**
   * The Price rule tax value currency.
   *
   * @var string
   */
  protected $tax_value_currency;

  /**
   * The weight of Price rule.
   *
   * @var int
   */
  protected $weight;

  /**
   * {@inheritdoc}
   */
  public function getPriceRuleType() {
    return $this->price_rule_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setPriceRuleType($price_rule_type) {
    $this->price_rule_type = $price_rule_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCondition() {
    return $this->condition;
  }

  /**
   * {@inheritdoc}
   */
  public function setCondition($condition) {
    $this->condition = $condition;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTaxType() {
    return $this->tax_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setTaxType($tax_type) {
    $this->tax_type = $tax_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTaxValue() {
    return $this->tax_value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTaxValue($tax_value) {
    $this->tax_value = $tax_value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTaxValueCurrency() {
    return $this->tax_value_currency;
  }

  /**
   * {@inheritdoc}
   */
  public function setTaxValueCurrency($tax_value_currency) {
    $this->tax_value_currency = $tax_value_currency;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }
}
