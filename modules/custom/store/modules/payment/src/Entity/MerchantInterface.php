<?php

namespace Drupal\payment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface for defining Merchant entities.
 */
interface MerchantInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets the merchant id.
   *
   * @return string
   */
  public function getMerchantId();

  /**
   * Sets the merchant id.
   *
   * @param string $merchant_id
   * @return static
   */
  public function setMerchantId($merchant_id);

  /**
   * Gets the merchant owner id.
   *
   * @return string
   */
  public function getCompanyId();

  /**
   * Set the merchant owner id.
   *
   * @param string $company_id
   * @return static
   */
  public function setCompanyId($company_id);

  /**
   * Gets payment method id that can use this merchant.
   *
   * @return array
   */
  public function getPaymentMethods();

  /**
   * Sets payment method id that can use this merchant.
   *
   * @param array $payment_methods
   * @return static
   */
  public function setPaymentMethods($payment_methods);

  /**
   * Gets payment adapter id that is used by this merchant.
   *
   * @return string
   */
  public function getPaymentAdapter();

  /**
   * Sets payment adapter id that is used by this merchant.
   *
   * @param string $payment_adapter
   * @return static
   */
  public function setPaymentAdapter($payment_adapter);

  /**
   * Gets the merchant config.
   *
   * @return array
   */
  public function getAdapterConfig();

  /**
   * Sets the merchant config.
   *
   * @param array $configs
   * @return static
   */
  public function setAdapterConfig(array $configs);

  /**
   * Gets the merchant specific configuration option.
   *
   * @param string $key
   * @return mixed
   */
  public function getAdapterConfigOption($key);

  /**
   * Checks if the merchant enabled.
   *
   * @return bool
   */
  public function isEnabled();

  /**
   * Creates the payment adapter plugin instance.
   *
   * @return \Drupal\payment\Plugin\PaymentAdapter\PaymentBaseAdapter
   */
  public function getPaymentAdapterPlugin();

}
