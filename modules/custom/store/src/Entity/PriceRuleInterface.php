<?php

namespace Drupal\store\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Price rule entities.
 */
interface PriceRuleInterface extends ConfigEntityInterface {

  /**
   * Gets the type of entity which requests Price rule.
   *
   * @return string
   */
  public function getPriceRuleType();

  /**
   * Sets the type of entity which requests Price rule.
   *
   * @param string $price_rule_type
   * @return static
   */
  public function setPriceRuleType($price_rule_type);

  /**
   * Gets the condition price rule.
   *
   * @return $condition
   */
  public function getCondition();

  /**
   * Sets the price rule condition.
   *
   * @param string $condition
   * @return static
   */
  public function setCondition($condition);

  /**
   * Gets the tex type price rule.
   *
   * @return string
   */
  public function getTaxType();

  /**
   * Sets the price rule tax type.
   *
   * @param string $tax_type
   * @return static
   */
  public function setTaxType($tax_type);

  /**
   * Gets the Tax Value price rule.
   *
   * @return float
   */
  public function getTaxValue();

  /**
   * Sets the price rule tax value.
   *
   * @param float $tax_value
   * @return static
   */
  public function setTaxValue($tax_value);

  /**
   * Gets the Tax Value Currency price rule.
   *
   * @return string
   */
  public function getTaxValueCurrency();

  /**
   * Sets the price rule tax value.
   *
   * @param string $tax_value_currency
   * @return static
   */
  public function setTaxValueCurrency($tax_value_currency);

  /**
   * Gets the weight price rule.
   *
   * @return int
   */
  public function getWeight();

  /**
   * Sets the weight price rule.
   *
   * @param int $weight
   * @return static
   */
  public function setWeight($weight);

}
