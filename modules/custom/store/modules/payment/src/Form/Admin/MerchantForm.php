<?php

namespace Drupal\payment\Form\Admin;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Plugin\PaymentAdapterManager;
use Drupal\payment\Plugin\PaymentMethodManager;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManager;
use Drupal\plugin\PluginType\PluginTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MerchantForm.
 *
 * @package Drupal\payment\Form
 */
class MerchantForm extends EntityForm {

  /**
   * @var \Drupal\payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * @var \Drupal\payment\Plugin\PaymentAdapterManager
   */
  protected $paymentAdapterManager;

  /**
   * @var \Drupal\plugin\PluginType\PluginTypeManager
   */
  protected $pluginTypeManager;

  /**
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManager
   */
  protected $pluginSelectorManager;

  /**
   * MerchantForm constructor.
   *
   * @param \Drupal\payment\Plugin\PaymentMethodManager $payment_method_manager
   * @param \Drupal\payment\Plugin\PaymentAdapterManager $payment_adapter_manager
   * @param \Drupal\plugin\PluginType\PluginTypeManager $plugin_type_manager
   * @param \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManager $plugin_selector_manager
   */
  public function __construct(PaymentMethodManager $payment_method_manager, PaymentAdapterManager $payment_adapter_manager, PluginTypeManager $plugin_type_manager, PluginSelectorManager $plugin_selector_manager) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->paymentAdapterManager = $payment_adapter_manager;
    $this->pluginTypeManager = $plugin_type_manager;
    $this->pluginSelectorManager = $plugin_selector_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.payment.payment_method'),
      $container->get('plugin.manager.payment.payment_adapter'),
      $container->get('plugin.plugin_type_manager'),
      $container->get('plugin.manager.plugin.plugin_selector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\payment\Entity\Merchant $merchant */
    $merchant = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant name'),
      '#maxlength' => 255,
      '#default_value' => $merchant->label(),
      '#description' => $this->t("The merchant internal name."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $merchant->id(),
      '#machine_name' => [
        'exists' => '\Drupal\payment\Entity\Merchant::load',
      ],
      '#disabled' => !$merchant->isNew(),
    ];

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#maxlength' => 255,
      '#default_value' => $merchant->getMerchantId(),
      '#description' => $this->t("Merchant ID that will be exported to SF into field Merchant_ID__c."),
      '#required' => TRUE,
    ];

    $form['company_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant'),
      '#maxlength' => 255,
      '#default_value' => $merchant->getCompanyId(),
      '#description' => $this->t("Merchant name that will be exported to SF into field AG__c."),
      '#required' => TRUE,
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#description' => $this->t('Enable to use on a payment form.'),
      '#default_value' => $merchant->isEnabled(),
    ];

    $form['payment_methods'] = [
      '#type' => 'select',
      '#multiple' => true,
      '#title' => $this->t('Payment methods'),
      '#description' => $this->t('Payment methods which can use this merchant.'),
      '#options' => $this->paymentMethodManager->getPaymentMethodOptions(),
      '#default_value' => !empty($merchant->getPaymentMethods()) ? array_combine($merchant->getPaymentMethods(), $merchant->getPaymentMethods()) : [],
      '#required' => true,
    ];

    $form['payment_adapter'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment adapter'),
      '#description' => $this->t('Which payment adapter can be used by this merchant.'),
      '#default_value' => $merchant->getPaymentAdapter(),
      '#options' => $this->paymentAdapterManager->getPaymentAdapterOptions(),
      '#required' => true,
      '#ajax' => [
        'callback' => [self::class, 'ajaxAdapterConfigForm'],
      ],
    ];

    $form['adapter_config'] = [
      '#type' => 'container',
      '#id' => 'payment-adapter-config-form',
      '#tree' => true,
    ];
    if ($merchant->getPaymentAdapter()) {
      /** @var \Drupal\payment\Plugin\PaymentAdapter\PaymentBaseAdapter $adapter */
      $adapter = $merchant->getPaymentAdapterPlugin();
      $form['adapter_config'] = array_merge($form['adapter_config'], $adapter->buildConfigurationForm($form['adapter_config'], $form_state));
    }

    return $form;
  }

  /**
   * Ajax callback, replaces payment adapter configuration form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public static function ajaxAdapterConfigForm(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#payment-adapter-config-form', $form['adapter_config']));
    return $response;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\payment\Entity\Merchant $merchant */
    $merchant = $this->entity;
    /** @var \Drupal\payment\Plugin\PaymentAdapter\PaymentBaseAdapter $adapter */
    $adapter = $merchant->getPaymentAdapterPlugin();
    $adapter->validateConfigurationForm($form['adapter_config'], $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\payment\Entity\Merchant $merchant */
    $merchant = $this->entity;
    /** @var \Drupal\payment\Plugin\PaymentAdapter\PaymentBaseAdapter $adapter */
    $adapter = $merchant->getPaymentAdapterPlugin();
    $adapter->submitConfigurationForm($form['adapter_config'], $form_state);

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $merchant = $this->entity;
    $status = $merchant->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Merchant.', [
          '%label' => $merchant->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Merchant.', [
          '%label' => $merchant->label(),
        ]));
    }
    $form_state->setRedirectUrl($merchant->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // Set plugin first since parent tries to initialize plugin collection.
    /** @var \Drupal\payment\Entity\Merchant $merchant */
    $merchant = $entity;
    $merchant->setPaymentAdapter($form_state->getValue('payment_adapter'));
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    $payment_methods = array_keys(array_filter($form_state->getValue('payment_methods')));
    $merchant->setPaymentMethods($payment_methods);
  }

  /**
   * Gets the plugin selector plugin instance.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface
   */
  protected function getSelector(FormStateInterface $form_state) {
    $key = 'plugin_selector';
    if ($form_state->has($key)) {
      $selector = $form_state->get($key);
    }
    else {
      $selector = $this->pluginSelectorManager->createInstance('plugin_select_list')
        ->setLabel($this->t('Payment adapter'))
        ->setDescription($this->t('A payment adapter used by this merchant.'))
        ->setRequired(true)
        ->setSelectablePluginType($this->pluginTypeManager->getPluginType('payment_adapter'));

      $form_state->set($key, $selector);
    }

    return $selector;
  }

  /**
   * Validates plugin selector form.
   *
   * Call this method to set selected plugin in selector.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function validateSelectorForm(array $form, FormStateInterface $form_state) {
    $selector = $this->getSelector($form_state);
    $selector->validateSelectorForm($form['payment_adapter'], $form_state);
  }

}
