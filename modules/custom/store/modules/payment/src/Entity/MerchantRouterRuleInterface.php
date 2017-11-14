<?php

namespace Drupal\payment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Merchant router rule entities.
 */
interface MerchantRouterRuleInterface extends ConfigEntityInterface {

  /**
   * Gets the merchant router weight.
   *
   * @return int
   */
  public function getWeight();

  /**
   * Sets the merchant router weight.
   *
   * @param int $weight
   * @return static
   */
  public function setWeight($weight);

  /**
   * Checks if merchants from the rule can be used to process the payment.
   *
   * @param array $vars
   * @return bool
   */
  public function isApplicable($vars);

  /**
   * Sets the merchant router rule condition.
   *
   * @param string $condition
   * @return static
   */
  public function setCondition($condition);

  /**
   * Gets the row merchant rule condition.
   *
   * @return string
   */
  public function getCondition();

  /**
   * Gets the list of merchant ids that can be used to process the payment.
   *
   * @return array
   */
  public function getMerchantIds();

  /**
   * Sets the list of merchant ids that can be used to process the payment.
   *
   * @param array $merchant_ids
   * @return static
   */
  public function setMerchantIds(array $merchant_ids);

}
