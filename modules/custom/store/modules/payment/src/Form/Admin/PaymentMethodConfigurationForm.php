<?php

namespace Drupal\payment\Form\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\payment\Plugin\PaymentMethodManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentMethodConfigurationForm extends FormBase {

  /**
   * @var \Drupal\payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * PaymentMethodConfigurationForm constructor.
   *
   * @param \Drupal\payment\Plugin\PaymentMethodManager $payment_method_manager
   */
  public function __construct(PaymentMethodManager $payment_method_manager) {
    $this->paymentMethodManager = $payment_method_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.payment.payment_method')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_method_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $payment_method = NULL) {
    if (!$payment_method) {
      throw new \InvalidArgumentException('Payment method is not provided');
    }

    $configs = $this->configFactory()->get('plugin.plugin_configuration.payment_method.' . $payment_method)->get();
    /** @var \Drupal\payment\Plugin\PaymentMethod\PaymentMethodBase $plugin */
    $plugin = $this->paymentMethodManager->createInstance($payment_method, $configs);
    $form = $plugin->buildConfigurationForm($form, $form_state);
    $form_state->set('plugin', $plugin);

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => new Url('payment.payment_config.payment_methods'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\payment\Plugin\PaymentMethod\PaymentMethodBase $plugin */
    $plugin = $form_state->get('plugin');
    $plugin->submitConfigurationForm($form, $form_state);
    $this->configFactory()
      ->getEditable('plugin.plugin_configuration.payment_method.' . $plugin->getBaseId())
      ->setData($plugin->getConfiguration())
      ->save();
  }

  /**
   * Gets title for payment method configuration page.
   *
   * @param string|null $payment_method
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function title($payment_method = NULL) {
    if (!$payment_method) {
      throw new \InvalidArgumentException('Payment method is not provided');
    }

    $definition = $this->paymentMethodManager->getDefinition($payment_method);

    return $this->t('@payment_method@ configuration', ['@payment_method@' => $definition['label']]);
  }

}
