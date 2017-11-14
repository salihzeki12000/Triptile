<?php

namespace Drupal\payment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Defines the Merchant entity.
 *
 * @ConfigEntityType(
 *   id = "merchant",
 *   label = @Translation("Merchant"),
 *   handlers = {
 *     "list_builder" = "Drupal\payment\MerchantListBuilder",
 *     "form" = {
 *       "add" = "Drupal\payment\Form\Admin\MerchantForm",
 *       "edit" = "Drupal\payment\Form\Admin\MerchantForm",
 *       "delete" = "Drupal\payment\Form\Admin\MerchantDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "merchant",
 *   admin_permission = "payment.administer:",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/store/payment/merchant/{merchant}",
 *     "add-form" = "/admin/store/payment/merchant/add",
 *     "edit-form" = "/admin/store/payment/merchant/{merchant}/edit",
 *     "delete-form" = "/admin/store/payment/merchant/{merchant}/delete",
 *     "collection" = "/admin/store/payment/merchant"
 *   }
 * )
 */
class Merchant extends ConfigEntityBase implements MerchantInterface {

  /**
   * The Merchant internal ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Merchant label.
   *
   * @var string
   */
  protected $label;

  /**
   * Merchant id.
   *
   * @var string
   */
  protected $merchant_id;

  /**
   * Merchant owner id.
   *
   * @var string
   */
  protected $company_id;

  /**
   * Payment method ids that can be used with this merchant.
   *
   * @var array
   */
  protected $payment_methods;

  /**
   * Payment methods settings.
   *
   * @var array
   */
  protected $payment_method_settings;

  /**
   * Payment adapter id used by this merchant.
   *
   * @var string
   */
  protected $payment_adapter;

  /**
   * Payment adapter specific configurations.
   *
   * @var array
   */
  protected $adapter_config = [];

  /**
   * @var \Drupal\Core\Plugin\DefaultLazyPluginCollection
   */
  protected $paymentMethodCollection;

  /**
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $paymentAdapterCollection;

  /**
   * {@inheritdoc}
   */
  public function getMerchantId() {
    return $this->merchant_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setMerchantId($merchant_id) {
    $this->merchant_id = $merchant_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompanyId() {
    return $this->company_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompanyId($company_id) {
    $this->company_id = $company_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethods() {
    return $this->payment_methods;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentMethods($payment_methods) {
    $this->payment_methods = $payment_methods;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentAdapter() {
    return $this->payment_adapter;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentAdapterPlugin() {
    return $this->getPaymentAdapterCollection()->get($this->payment_adapter);
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentAdapter($payment_adapter) {
    $this->payment_adapter = $payment_adapter;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapterConfig() {
    return $this->adapter_config;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdapterConfig(array $configs) {
    $this->adapter_config = $configs;
    $this->paymentAdapterCollection = null;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapterConfigOption($key) {
    return isset($this->adapter_config[$key]) ? $this->adapter_config[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'method_settings' => $this->getPaymentMethodCollection(),
      'adapter_config' => $this->getPaymentAdapterCollection(),
    ];
  }

  protected function getPaymentMethodCollection() {
    if (!$this->paymentMethodCollection) {
      $this->paymentMethodCollection = new DefaultLazyPluginCollection(\Drupal::service('plugin.manager.payment.payment_method'));
    }

    return $this->paymentMethodCollection;
  }

  protected function getPaymentAdapterCollection() {
    if (!$this->paymentAdapterCollection && $this->payment_adapter) {
      $this->paymentAdapterCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.payment.payment_adapter'), $this->payment_adapter, $this->adapter_config);
    }

    return $this->paymentAdapterCollection;
  }

}
