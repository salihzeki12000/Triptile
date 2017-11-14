<?php

namespace Drupal\payment\Plugin\PaymentMethod;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use Drupal\address\FieldHelper;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CreditCard
 * @PaymentMethod(
 *   id = "credit_card",
 *   label = @Translation("Credit card"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class CreditCardPaymentMethod extends PaymentMethodBase {

  /**
   * Card types
   */
  const
    CARD_TYPE_VISA = 'visa',
    CARD_TYPE_MASTERCARD = 'mc',
    CARD_TYPE_DISCOVER = 'discover',
    CARD_TYPE_AMERICAN_EXPRESS = 'amex';

  /**
   * The array of countries codes, which should be on the top of the list.
   */
  protected static $topCountryList = ['US', 'CA', 'GB', 'AU', 'NZ', 'AR', 'FR', 'DE'];

  /**
   * @var \Drupal\address\Repository\CountryRepository
   */
  protected $countryRepository;

  /**
   * @var \Drupal\address\Repository\SubdivisionRepository
   */
  protected $subdivisionRepository;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * CreditCard constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // @todo Use DI container.
    // @todo Use our custom country service
    $this->subdivisionRepository = \Drupal::service('address.subdivision_repository');
    $this->currentUser = \Drupal::currentUser();
  }

  /**
   * Gets array of card type names keyed by its code.
   *
   * @param array $types
   * @return array
   */
  public static function cardTypeOptions($types = []) {
    $options = [
      static::CARD_TYPE_VISA => t('Visa'),
      static::CARD_TYPE_MASTERCARD => t('Mastercard'),
      static::CARD_TYPE_DISCOVER => t('Discover'),
      static::CARD_TYPE_AMERICAN_EXPRESS => t('American Express'),
    ];

    if (empty($types)) {
      return $options;
    }

    $return_options = [];
    foreach ($types as $type) {
      if (isset($options[$type])) {
        $return_options[$type] = $options[$type];
      }
    }

    return $return_options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configs = parent::defaultConfiguration();
    $configs['allowed_card_types'] = [
      static::CARD_TYPE_VISA,
      static::CARD_TYPE_MASTERCARD,
      static::CARD_TYPE_DISCOVER
    ];

    return $configs;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['allowed_card_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed card types'),
      '#default_value' => array_combine($this->getCardTypes(), $this->getCardTypes()),
      '#options' => static::cardTypeOptions(),
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
      $this->configuration['allowed_card_types'] = array_keys(array_filter($values['allowed_card_types']));
    }
  }

  /**
   * Gets card types configuration.
   *
   * @return array
   */
  public function getCardTypes() {
    return $this->configuration['allowed_card_types'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentDataForm(array $form, FormStateInterface $form_state, $include_title = false) {
    $form = parent::buildPaymentDataForm($form, $form_state);

    $form['card_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Card type'),
      '#options' => static::cardTypeOptions($this->getCardTypes()),
      '#empty_value' => '',
      '#empty_option' => $this->t(' '),
      '#attributes' => [
        'data-placeholder' => $this->t(' '),
      ],
    ];

    $form['card_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card number'),
      '#description' => $this->t('Card number without spaces, dashes or dots.'),
      '#maxlength' => 19,
    ];

    $date = new \DateTime();
    $year = $date->format('Y');
    $form['card_expiration_date'] = [
      '#type' => 'container',
      '#tree' => true,
    ];
    $form['card_expiration_date']['label'] = [
      '#type' => 'label',
      '#title' => $this->t('Expiration date'),
    ];

    $form['card_expiration_date']['dates'] = [
      '#type' => 'container',
      '#tree' => true,
      '#attributes' => [
        'class' => 'expiration-dates'
      ]
    ];

    $form['card_expiration_date']['dates']['month'] = [
      '#type' => 'select',
      '#options' => array_combine(range(1, 12), range(1, 12)),
      '#empty_value' => '',
      '#empty_option' => $this->t('- Month -'),
      '#default_value' => '',
    ];
    $form['card_expiration_date']['dates']['year'] = [
      '#type' => 'select',
      '#options' => array_combine(range($year, $year + 10), range($year, $year + 10)),
      '#empty_value' => '',
      '#empty_option' => $this->t('- Year -'),
      '#default_value' => '',
    ];

    $form['card_owner'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card owner'),
    ];

    $form['card_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CVC'),
      '#maxlength' => 4,
    ];

    if ($include_title) {
      $form['#title'] = $this->t('Credit card details');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildBillingProfileForm(array $form, FormStateInterface $form_state, $include_title = FALSE) {
    $form = parent::buildBillingProfileForm($form, $form_state, $include_title);

    $profile = null;
    if (!$this->currentUser->isAnonymous()) {
      $ids = $this->entityQuery->get('customer_profile', 'AND')
        ->condition('uid.target_id', $this->currentUser->id())
        ->execute();
      if (!empty($ids)) {
        /** @var \Drupal\store\Entity\CustomerProfile $profile */
        $profile = $this->entityTypeManager->getStorage('customer_profile')->load(max($ids));
      }
    }

    $form['#attached']['library'][] = 'payment/billing-profile';

    // Address form must be similar to address field widget.

    // Move to the top countries from $topCountryList.
    $countryList = $this->countryRepository->getList();
    $topCountryList = [];
    foreach (static::$topCountryList as $countryCode) {
      $topCountryList[$countryCode] = $countryList[$countryCode];
    }
    $countryList = $topCountryList + $countryList;

    // @todo Get country by IP.
    $country_code = $profile ? $profile->getAddress()->getCountryCode() : null;
    $form['country_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Billing country'),
      '#options' => $countryList,
      '#empty_value' => '',
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $country_code ? : '',
    ];

    // States are used for native select (on mobile), js handles selectize.
    $states = [
      'visible' => [
        '[name*=country_code]' => ['value' => 'US'],
      ],
    ];

    $form[FieldHelper::getPropertyName(AddressField::ADDRESS_LINE1)] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address'),
      '#states' => $states,
      '#default_value' => $profile ? $profile->getAddress()->getAddressLine1() : '',
    ];

    $form[FieldHelper::getPropertyName(AddressField::POSTAL_CODE)] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zip code'),
      '#states' => $states,
      '#default_value' => $profile ? $profile->getAddress()->getPostalCode() : '',
    ];

    $form[FieldHelper::getPropertyName(AddressField::LOCALITY)] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#states' => $states,
      '#default_value' => $profile ? $profile->getAddress()->getLocality() : '',
    ];

    $form[FieldHelper::getPropertyName(AddressField::ADMINISTRATIVE_AREA)] = [
      '#type' => 'select',
      '#title' => $this->t('State'),
      '#options' => $this->subdivisionRepository->getList(['US']),
      '#empty_value' => '',
      '#empty_option' => $this->t('- Select -'),
      '#states' => $states,
      '#default_value' => $profile ? $profile->getAddress()->getAdministrativeArea() : '',
    ];

    $form['phone_number'] = [
      '#type' => 'tel',
      '#title' => $this->t('Contact phone'),
      '#description' => $this->t('Phone number to reach you in case of questions.'),
      '#default_value' => $profile ? $profile->getPhoneNumber() : '',
    ];

    // @todo Do not display the field for logged in users.
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Contact email address'),
      '#default_value' => $profile ? $profile->getEmail() : '',
    ];

    if ($include_title) {
      $form['#title'] = $this->t('Billing information');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaymentDataForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $required = 'Field @title is required.';

    if (empty($values['card_number'])) {
      $form_state->setError($form['card_number'], $this->t($required, ['@title' => $form['card_number']['#title']]));
    }
    elseif (!$this->validateCardNumber($values['card_number'])) {
      $form_state->setError($form['card_number'], $this->t('Card number is invalid.'));
    }

    if (empty($values['card_type'])) {
      $form_state->setError($form['card_type'], $this->t($required, ['@title' => $form['card_type']['#title']]));
    }

    if (!empty($values['card_type'] && !empty($values['card_number']) && !$this->validateCardType($values['card_number'], $values['card_type']))) {
      $form_state->setError($form['card_number'], $this->t('Card type doesn\'t correspond to the card number.'));
      $form_state->setError($form['card_type']);
    }

    if (empty($values['card_owner'])) {
      $form_state->setError($form['card_owner'], $this->t($required, ['@title' => $form['card_owner']['#title']]));
    }

    if (empty($values['card_expiration_date']['dates']['month']) || empty($values['card_expiration_date']['dates']['year'])) {
      $form_state->setError($form['card_expiration_date']['dates']['month'], $this->t($required, ['@title' => $form['card_expiration_date']['label']['#title']]));
      $form_state->setError($form['card_expiration_date']['dates']['year']);
    }

    if (empty($values['card_code'])) {
      $form_state->setError($form['card_code'], $this->t($required, ['@title' => $form['card_code']['#title']]));
    }
    elseif (!preg_match('/^[\d]{3,4}$/', $values['card_code'])) {
      $form_state->setError($form['card_code'], $this->t('Card security code is invalid.'));
    };

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function validateBillingProfileForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    if (empty($values['country_code'])) {
      $form_state->setError($form['country_code'], $this->t('Field @title is required.', ['@title' => $form['country_code']['#title']]));
    }

    // @todo Use countries from config
    if (in_array($values['country_code'], ['US'])) {
      foreach (['address_line1', 'postal_code', 'locality', 'administrative_area'] as $key) {
        if (empty($values[$key])) {
          $form_state->setError($form[$key], $this->t('Field @title is required.', ['@title' => $form[$key]['#title']]));
        }
      }
    }

    if (empty($values['phone_number'])) {
      $form_state->setError($form['phone_number'], $this->t('Field @title is required.', ['@title' => $form['phone_number']['#title']]));
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaymentDataForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $this->setPaymentData($values);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function submitBillingProfileForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    if ($values['country_code'] != 'US') {
      unset($values[FieldHelper::getPropertyName(AddressField::ADMINISTRATIVE_AREA)]);
    }
    $this->setBillingData($values);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentData(array $payment_data) {
    $result = parent::setPaymentData($payment_data);

    list($first_name, $last_name) = explode(' ', $payment_data['card_owner'], 2);
    $this->billingData[FieldHelper::getPropertyName(AddressField::GIVEN_NAME)] = $first_name;
    $this->billingData[FieldHelper::getPropertyName(AddressField::FAMILY_NAME)] = empty($last_name) ? $first_name : $last_name;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingData(array $billing_data) {
    $this->billingData = empty($this->billingData) ? $billing_data : array_merge($this->billingData, $billing_data);
    return $this;
  }

  /**
   * Validates card number using Luhn algorithm.
   *
   * @param $card_number
   * @return bool
   */
  protected function validateCardNumber($card_number) {
    if (!preg_match('/^([\d]{13}|[\d]{15,16}|[\d]{19})$/', $card_number)) {
      return false;
    };

    $sumTable = [
      [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
      [0, 2, 4, 6, 8, 1, 3, 5, 7, 9],
    ];
    $sum = 0;
    $flip = 0;
    for ($i = strlen($card_number) - 1; $i >= 0; $i--) {
      $sum += $sumTable[$flip++ & 0x1][$card_number[$i]];
    }

    return $sum % 10 === 0;
  }

  /**
   * Checks if card type corresponds to card number.
   *
   * @param $card_number
   * @param $card_type
   * @return bool
   */
  protected function validateCardType($card_number, $card_type) {
    switch ($card_type) {
      case static::CARD_TYPE_VISA:
        return substr($card_number, 0, 1) == 4;

      case static::CARD_TYPE_MASTERCARD:
        return (substr($card_number, 0, 2) >= 51 && substr($card_number, 0, 2) <= 55)
          || (substr($card_number, 0, 4) >= 2221 && substr($card_number, 0, 4) <= 2720);

      case static::CARD_TYPE_DISCOVER:
        return (substr($card_number, 0, 2) == 65)
          || (substr($card_number, 0, 3) >= 644 && substr($card_number, 0, 2) <= 649)
          || (substr($card_number, 0, 6) >= 622126 && substr($card_number, 0, 6) <= 622925);

      case static::CARD_TYPE_AMERICAN_EXPRESS:
        return (in_array(substr($card_number, 0, 2), [34,37]));
    }

    return false;
  }

}
