<?php

namespace Drupal\payment\Plugin\PaymentMethod;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use Drupal\address\FieldHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\API\Paysera\WebToPayException;

trait PayseraPaymentMethodTrait {

  /**
   * The array of countries codes, which should be on the top of the list.
   */
  protected static $topCountryList = ['US', 'CA', 'GB', 'AU', 'NZ', 'AR', 'FR', 'DE'];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configs = parent::defaultConfiguration();
    $configs['projectid'] = '';
    $configs['display_payment_form_text'] = false;
    $configs['payment_form_text'] = [
      'value' => '',
      'format' => 'full_html',
    ];

    return $configs;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['projectid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project id in Paysera'),
      '#default_value' => $this->configuration['projectid'],
    ];

    $form['display_payment_form_text'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display text below the payment methods list.'),
      '#default_value' => $this->configuration['display_payment_form_text'],
    ];

    $form['payment_form_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Text below the payment methods list.'),
      '#default_value' => $this->configuration['payment_form_text']['value'],
      '#format' => $this->configuration['payment_form_text']['format'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['projectid'] = $values['projectid'];
      $this->configuration['display_payment_form_text'] = $values['display_payment_form_text'];
      $this->configuration['payment_form_text'] = $values['payment_form_text'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $isEnabled = parent::isEnabled();
    if ($isEnabled && !empty($this->configuration['projectid'])) {
      try {
        $paymentMethods = $this->getApi()->getPaymentMethodList();
        $currentPaymentMethodName = $this->getPayseraPaymentName();
        $isEnabled = false;
        foreach ($paymentMethods->getCountries() as $paymentMethodCountry) {
          foreach ($paymentMethodCountry->getPaymentMethods() as $paymentMethod) {
            if ($paymentMethod->getKey() == $currentPaymentMethodName) {
              $isEnabled = true;
            }
          }
        }
      }
      catch (WebToPayException $exception) {
        watchdog_exception('payment', $exception);
      }
    }

    return $isEnabled;
  }

  /**
   * {@inheritdoc}
   */
  public function getPayseraPaymentName() {
    return static::$payseraPaymentName;
  }

  /**
   *  Gets paysera API class instance.
   *
   * @return \Drupal\payment\API\PayseraAPI
   */
  protected function getApi() {
    return \Drupal::service('payment.api')->get('paysera')->setConfig($this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function buildBillingProfileForm(array $form, FormStateInterface $form_state, $include_title = FALSE) {
    $form = parent::buildBillingProfileForm($form, $form_state, $include_title);

    $profile = null;
    if (!\Drupal::currentUser()->isAnonymous()) {
      $ids = $this->entityQuery->get('customer_profile', 'AND')
        ->condition('uid.target_id', \Drupal::currentUser()->id())
        ->execute();
      if (!empty($ids)) {
        /** @var \Drupal\store\Entity\CustomerProfile $profile */
        $profile = $this->entityTypeManager->getStorage('customer_profile')->load(max($ids));
      }
    }

    if ($profile) {
      $form['country_code'] = [
        '#type' => 'value',
        '#value' => $profile->getAddress()->getCountryCode(),
      ];
      $form[FieldHelper::getPropertyName(AddressField::GIVEN_NAME)] = [
        '#type' => 'value',
        '#value' => $profile->getAddress()->getGivenName(),
      ];
      $form[FieldHelper::getPropertyName(AddressField::FAMILY_NAME)] = [
        '#type' => 'value',
        '#value' => $profile->getAddress()->getFamilyName(),
      ];
      $form['email'] = [
        '#type' => 'email',
        '#value' => $profile->getEmail(),
      ];
    }
    else {
      // Address form must be similar to address field widget.

      // @todo Get country by IP.
      if (isset(static::$billingCountry)) {
        $form['country_code'] = [
          '#type' => 'value',
          '#value' => static::$billingCountry,
        ];
      }
      else {
        // Move to the top countries from $topCountryList.
        $countryList = $this->countryRepository->getList();
        $topCountryList = [];
        foreach (static::$topCountryList as $countryCode) {
          $topCountryList[$countryCode] = $countryList[$countryCode];
        }
        $countryList = $topCountryList + $countryList;
        $form['country_code'] = [
          '#type' => 'select',
          '#title' => $this->t('Billing country'),
          '#options' => $countryList,
          '#empty_value' => '',
          '#empty_option' => $this->t('- Select -'),
        ];
      }

      $form[FieldHelper::getPropertyName(AddressField::GIVEN_NAME)] = [
        '#type' => 'textfield',
        '#title' => $this->t('First name'),
      ];

      $form[FieldHelper::getPropertyName(AddressField::FAMILY_NAME)] = [
        '#type' => 'textfield',
        '#title' => $this->t('Second name'),
      ];

      // @todo Do not display the field for logged in users.
      $form['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Contact email address'),
      ];

      if ($include_title) {
        $form['#title'] = $this->t('Billing information');
      }

    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateBillingProfileForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    foreach (['country_code', FieldHelper::getPropertyName(AddressField::GIVEN_NAME), FieldHelper::getPropertyName(AddressField::FAMILY_NAME), 'email'] as $key) {
      if (empty($values[$key])) {
        $form_state->setError($form[$key], $this->t('Field @title is required.', ['@title' => $form[$key]['#title']]));
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function submitBillingProfileForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $this->setBillingData($values);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentDataForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildPaymentDataForm($form, $form_state);
    if ($this->configuration['display_payment_form_text']) {
      $form['text']['#markup'] = check_markup($this->configuration['payment_form_text']['value'], $this->configuration['payment_form_text']['format']);
      $form['#attributes']['class'][] = 'fieldset-no-title';
      $form['#attributes']['class'][] = 'fieldset-paysera';
    }

    return $form;
  }

}
