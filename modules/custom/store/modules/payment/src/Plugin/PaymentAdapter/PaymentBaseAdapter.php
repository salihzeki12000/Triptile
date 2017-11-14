<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\payment\Entity\TransactionInterface;

/**
 * Base class for Payment adapter plugins.
 */
abstract class PaymentBaseAdapter extends PluginBase implements PaymentAdapterInterface {

  // @todo Add support of DI.
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * @var \Drupal\currency\FormHelper
   */
  protected $currencyFormHelper;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\payment\API\PaymentAPIFactory
   */
  protected $apiFactory;


  /**
   * PaymentBaseAdapter constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // @todo Add support of DI
    $this->currencyFormHelper = \Drupal::service('currency.form_helper');
    $this->logger = \Drupal::logger('payment');
    $this->configFactory = \Drupal::configFactory();
    $this->apiFactory = \Drupal::service('payment.api');

    if (empty($this->getConfiguration())) {
      $this->setConfiguration($this->defaultConfiguration());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $storeConfig = $this->configFactory->get('store.settings');
    return [
      'sandbox_mode' => false,
      'default_currency' => $storeConfig->get('global_currency') ?: 'USD',
      'supported_currencies' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // TODO: Implement calculateDependencies() method.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['sandbox_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use sandbox'),
      '#default_value' => $this->isInSandboxMode(),
    ];

    $currency_options = $this->currencyFormHelper->getCurrencyOptions();
    unset($currency_options['XXX']);
    $form['default_currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Default currency'),
      '#description' => $this->t('All amounts in unsupported currencies will be converted to this currency.'),
      '#default_value' => $this->getDefaultCurrency(),
      '#options' => $currency_options,
    ];

    $form['supported_currencies'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Supported currencies'),
      '#description' => $this->t('Additional currencies that can be used to process a payment.'),
      '#default_value' => array_combine($this->getSupportedCurrencies(), $this->getSupportedCurrencies()),
      '#options' => $currency_options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['sandbox_mode'] = (bool) $values['sandbox_mode'];
      $this->configuration['default_currency'] = $values['default_currency'];
      $this->configuration['supported_currencies'] = array_keys(array_filter($values['supported_currencies']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isInSandboxMode() {
    return (bool) $this->configuration['sandbox_mode'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultCurrency() {
    return $this->configuration['default_currency'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCurrencies() {
    return $this->configuration['supported_currencies'];
  }

  /**
   * Gets price object that can be used to process the transaction.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return \Drupal\store\Price
   */
  protected function calculateAmount(TransactionInterface $transaction) {
    $currency = $this->getDefaultCurrency();
    if (in_array($transaction->getOriginalAmount()->getCurrencyCode(), $this->getSupportedCurrencies())) {
      $currency = $transaction->getOriginalAmount()->getCurrencyCode();
    }
    return $transaction->getOriginalAmount()->convert($currency);
  }

  /**
   * Gets a new instance of API class.
   *
   * @return \Drupal\payment\API\PaymentAPIBase
   */
  protected function getAPI() {
    return $this->apiFactory->get($this->getPluginDefinition()['payment_system'])->setConfig($this->configuration);
  }

}
