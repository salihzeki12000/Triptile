<?php

namespace Drupal\payment\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\store\Entity\Invoice;
use Drupal\Component\Utility\Html;

trait PaymentFormTrait {

  /**
   * @var \Drupal\payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * @var \Drupal\payment\Plugin\PaymentMethod\PaymentMethodBase[]
   */
  protected $plugins;

  /**
   * @var \Drupal\Core\Url
   */
  protected $successUrl;

  /**
   * @var \Drupal\Core\Url
   */
  protected $failUrl;

  /**
   * @var \Drupal\Core\Url
   */
  protected $cancelUrl;

  /**
   * Builds the invoice payment form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\store\Entity\Invoice $invoice
   * @return array
   */
  protected function buildPaymentForm(array $form, FormStateInterface $form_state, Invoice $invoice) {
    $form_state->set('invoice', $invoice);

    $form['payment_method_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Payment method'),
      '#weight' => -10,
      '#attributes' => [
        'class' => [
          'payment-method',
          'number-1'
        ]
      ]
    ];
    $form['payment_method_wrapper']['payment_method'] = [
      '#type' => 'radios_with_hidden_options',
      '#title' => $this->t('Payment method'),
      '#title_display' => 'invisible',
      '#required' => true,
      '#default_value' => $form_state->getValue('payment_method'),
      '#attributes' => [
        'class' => [
          'payment-method-options'
        ]
      ]
    ];

    $paymentMethods = [];
    $topCount = 0;
    foreach ($this->paymentMethodManager->getDefinitions() as $plugin_id => $definition) {
      $plugin = $this->getPaymentMethodPlugin($plugin_id, $invoice);
      if ($plugin->isEnabled()) {
        $weight = $plugin->isTop() ? $plugin->getWeight() - 10000 : $plugin->getWeight();
        if ($plugin->isTop()) {
          $topCount++;
        }
        $paymentMethods[$weight] = $plugin_id;
      }
    }
    $form['payment_method_wrapper']['payment_method']['#visible_count'] = $topCount;
    ksort($paymentMethods);
    $paymentMethodOptions = [];
    foreach ($paymentMethods as $plugin_id) {
      $plugin = $this->getPaymentMethodPlugin($plugin_id, $invoice);
      $paymentMethodOptions[$plugin_id] = $plugin->getPluginDefinition()['label'];
      $form[$plugin_id] = [
        '#type' => 'container',
        '#tree' => true,
      ];

      $container = [
        '#type' => 'fieldset',
        '#states' => [
          'visible' => [
            '[data-drupal-selector^=edit-payment-method]' => ['value' => $plugin_id],
          ],
        ],
        '#attributes' => [
          'class' => [
            strtolower(Html::cleanCssIdentifier($plugin_id))
          ]
        ]
      ];

      if ($paymentForm = $plugin->buildPaymentDataForm([], $form_state, true)) {
        $form[$plugin_id]['payment_data'] = array_merge($container, $paymentForm);
        $form[$plugin_id]['payment_data']['#attributes']['class'][] = 'payment-data';
      }
      if ($billingForm = $plugin->buildBillingProfileForm([], $form_state, true)) {
        $form[$plugin_id]['billing_profile'] = array_merge($container, $billingForm);
        $form[$plugin_id]['billing_profile']['#attributes']['class'][] = 'billing-profile';
      }
    }

    $form['payment_method_wrapper']['payment_method']['#options'] = $paymentMethodOptions;

    foreach($form['payment_method_wrapper']['payment_method']['#options'] as $plugin_id => $value) {
      $form['payment_method_wrapper']['payment_method'][$plugin_id]['#attributes']['data'][] = strtolower(Html::cleanCssIdentifier($plugin_id));
      $form['payment_method_wrapper']['payment_method'][$plugin_id]['#attributes']['class'][] = strtolower(Html::cleanCssIdentifier($plugin_id));
    }

    $form['terms_and_conditions_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Accept terms & conditions'),
      '#attributes' => [
        'class' => [
          'terms-and-conditions',
          'number-2'
        ]
      ]
    ];
    $form['terms_and_conditions_wrapper']['text_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'text-wrapper'
        ]
      ]
    ];
    $form['terms_and_conditions_wrapper']['accept'] = [
      '#type' => 'checkbox',
      '#title' => '<span>' . $this->t('By selecting to complete this booking I acknowledge that I have read and accept Company\'s Terms of Service, of Use. <a href="/terms-and-conditions">Read terms</a>').'</span>',
      '#required' => true,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Pay now'),
    ];

    return  $form;
  }

  /**
   * Validates payment form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function validatePaymentForm(array $form, FormStateInterface $form_state) {
    $plugin_id = $form_state->getValue('payment_method');
    $invoice = $form_state->get('invoice');
    if ($plugin_id) {
      $plugin = $this->getPaymentMethodPlugin($plugin_id, $invoice);
      if (!$plugin->isEnabled()) {
        $form_state->setErrorByName('payment_method', $this->t('@payment_method cant\'t be used to process this payment.', array('@payment_method' => $plugin->getPluginDefinition()['label'])));
      }

      if (isset($form[$plugin->getBaseId()]['payment_data'])) {
        $plugin->validatePaymentDataForm($form[$plugin->getBaseId()]['payment_data'], $form_state);
      }
      if (isset($form[$plugin->getBaseId()]['billing_profile'])) {
        $plugin->validateBillingProfileForm($form[$plugin->getBaseId()]['billing_profile'], $form_state);
      }
    }
    else {
      $form_state->setErrorByName('payment_method', $this->t('Please, choose some payment method.', [], ['context' => 'Payment Form']));
    }
  }

  /**
   * Processes payment using data from payment form. Appropriate redirect will
   * be set in the $form_state.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function submitPaymentForm(array $form, FormStateInterface $form_state) {
    $invoice = $form_state->get('invoice');
    $plugin = $this->getPaymentMethodPlugin($form_state->getValue('payment_method'), $invoice);
    if (isset($form[$plugin->getBaseId()]['payment_data'])) {
      $plugin->submitPaymentDataForm($form[$plugin->getBaseId()]['payment_data'], $form_state);
    }
    if (isset($form[$plugin->getBaseId()]['billing_profile'])) {
      $plugin->submitBillingProfileForm($form[$plugin->getBaseId()]['billing_profile'], $form_state);
    }
    $plugin->setSuccessUrl($this->successUrl)
      ->setCancelUrl($this->cancelUrl)
      ->setFailUrl($this->failUrl);

    $response = new TrustedRedirectResponse($plugin->processPayment()->toString());
    $response->getCacheableMetadata()->setCacheMaxAge(0);
    $form_state->setResponse($response);
  }

  /**
   * Gets the payment method plugin instance.
   *
   * @param string $plugin_id
   * @param \Drupal\store\Entity\Invoice $invoice
   * @return \Drupal\payment\Plugin\PaymentMethod\PaymentMethodBase
   */
  protected function getPaymentMethodPlugin($plugin_id, Invoice $invoice) {
    if (!isset($this->plugins[$plugin_id])) {
      $config = $this->configFactory()
        ->get('plugin.plugin_configuration.payment_method.' . $plugin_id)
        ->get();
      $this->plugins[$plugin_id] = $this->paymentMethodManager->createInstance($plugin_id, $config)
        ->setInvoice($invoice);
    }

    return $this->plugins[$plugin_id];
  }

  /**
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected function configFactory() {
    return \Drupal::configFactory();
  }

  /**
   * Sets the payment success url.
   *
   * @param \Drupal\Core\Url $url
   * @return static
   */
  public function setSuccessUrl(Url $url) {
    $this->successUrl = $url;
    return $this;
  }

  /**
   * Sets the payment cancel url.
   *
   * @param \Drupal\Core\Url $url
   * @return static
   */
  public function setCancelUrl(Url $url) {
    $this->cancelUrl = $url;
    return $this;
  }

  /**
   * Sets the payment fail url.
   *
   * @param \Drupal\Core\Url $url
   * @return static
   */
  public function setFailUrl(Url $url) {
    $this->failUrl = $url;
    return $this;
  }

}
