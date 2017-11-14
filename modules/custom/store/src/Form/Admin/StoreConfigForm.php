<?php

namespace Drupal\store\Form\Admin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\currency\FormHelperInterface;
use Drupal\master\Form\Admin\ConfigForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class StoreConfigForm extends ConfigForm {

  /**
   * Mapping of country code to currency.
   *
   * @var array
   */
  protected static $countryCurrencyMapping = [
    'US' => 'USD',
    'GB' => 'GBP',
    'DE' => 'EUR',
    'RU' => 'USD',
    'AT' => 'EUR',
    'BE' => 'EUR',
    'GR' => 'EUR',
    'IE' => 'EUR',
    'ES' => 'EUR',
    'IT' => 'EUR',
    'LV' => 'EUR',
    'LT' => 'EUR',
    'LU' => 'EUR',
    'NL' => 'EUR',
    'PT' => 'EUR',
    'SK' => 'EUR',
    'SI' => 'EUR',
    'FI' => 'EUR',
    'FR' => 'EUR',
    'EE' => 'EUR',
  ];

  /**
   * @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage
   */
  protected $expressionLanguage;

  /**
   * StoreConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   * @param \Drupal\currency\FormHelperInterface $form_helper
   * @param \Symfony\Component\ExpressionLanguage\ExpressionLanguage $expression_language
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager, CountryManagerInterface $country_manager, FormHelperInterface $form_helper, ExpressionLanguage $expression_language) {
    parent::__construct($config_factory, $entity_type_manager, $country_manager, $form_helper);

    $this->expressionLanguage = $expression_language;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('country_manager'),
      $container->get('currency.form_helper'),
      $container->get('master.expression_language')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'store_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->setCached(FALSE);
    // Get currency options (available).
    $currency_options = \Drupal::service('currency.form_helper')->getCurrencyOptions();
    // @todo will delete on production.
    unset($currency_options['XXX']);

    $config = $this->configFactory->get('store.settings');

    // Salesforce.
    $opportunityId = $config->get('opportunity_id_for_payable_invoices');
    $form['salesforce_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Salesforce'),
    ];
    $form['salesforce_fieldset']['opportunity_id'] = [
      '#title' => $this->t('Opportunity ID for payable invoices'),
      '#type' => 'textfield',
      '#default_value' => !empty($opportunityId) ? $opportunityId : null,
    ];
    $form['salesforce_fieldset']['order_verification_condition'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Condition to set opportunity for verification'),
      '#default_value' => $config->get('order_verification_condition'),
      '#required' => true,
      '#description' => $this->t('Next variables are available:<ul>'
        . '<li><code>currency</code> - currency of the order;</li>'
        . '<li><code>language</code> - language of the order;</li>'
        . '<li><code>billing_country</code> - 2-letter code of the client\'s billing country if available;</li>'
        . '<li><code>email</code> - the email user specified on booking;</li>'
        . '<li><code>ip_country</code> - country detected by client\'s IP address (IP address is taken from the latest transaction);</li>'
        . '<li><code>merchant_id</code> - merchant ID from the successful transaction;</li>'
        . '<li><code>failed_transactions</code> - count of failed transactions (pending transactions are not failed);</li>'
        . '<li><code>depth</code> - depth of the order;</li>'
        . '<li><code>suppliers</code> - array of suppliers from the order (use in condition as <code>\'UFS\' in suppliers</code>);</li>'
        . '</ul>'),
    ];

    // Global site currency.
    $global_currency = $config->get('global_currency');
    $form['global_currency_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Global site currency'),
    ];
    $form['global_currency_fieldset']['global_currency'] = [
      '#title' => $this->t('Global currency'),
      '#type' => 'select',
      '#options' => $currency_options,
      '#default_value' => !empty($global_currency) ? $global_currency : null,
      '#empty_option' => t('- None -'),
    ];

    // Visible currencies.
    $visible_currencies = $config->get('visible_currencies');
    $form['visible_currencies_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Visible currencies'),
    ];
    $form['visible_currencies_fieldset']['visible_currencies'] = [
      '#type' => 'checkboxes',
      '#options' => $currency_options,
      '#default_value' => $visible_currencies,
    ];

    // Country currency.
    $country_currency = $config->get('country_currency') ?: [];
    if (empty($country_currency)) {
      foreach (static::$countryCurrencyMapping as $country_code => $currency_code) {
        $country_currency[] = ['country_code' => $country_code, 'currency_code' => $currency_code];
      }
    }
    $name_field = $form_state->get('num_countries');
    $form['#tree'] = TRUE;
    $form['country_currency_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Country currency'),
      '#prefix' => '<div id="country-currency-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    if (!isset($name_field)) {
      $name_field = count($country_currency);
      $form_state->set('num_countries', $name_field);
    }
    $form['country_currency_fieldset']['country_currency'] = [
      '#type' => 'container',
    ];
    for ($i = 0; $i < $name_field; $i++) {
      $form['country_currency_fieldset']['country_currency'][$i] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'container-inline'
          ],
        ],
      ];
      $form['country_currency_fieldset']['country_currency'][$i]['country_code'] = [
        '#type' => 'select',
        //'#title' => t('Country', [], ['context' => 'Store config Form']),
        '#options' => $this->countryManager->getList(),
        '#default_value' => $country_currency[$i]['country_code'],
        '#empty_option' => t('- None -'),
      ];
      $form['country_currency_fieldset']['country_currency'][$i]['currency_code'] = [
        '#type' => 'select',
        //'#title' => t('Currency', [], ['context' => 'Store config Form']),
        '#options' => $currency_options,
        '#default_value' => $country_currency[$i]['currency_code'],
        '#empty_option' => t('- None -'),
      ];
    }
    $form['country_currency_fieldset']['actions']['add_country'] = [
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#submit' => array('::addOne'),
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'country-currency-fieldset-wrapper',
      ],
    ];
    if ($name_field > 0) {
      $form['country_currency_fieldset']['actions']['remove_country'] = [
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#submit' => array('::removeCallback'),
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'country-currency-fieldset-wrapper',
        ],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the stations in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    $country_currency_field = $form_state->get('num_countries');
    return $form['country_currency_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $country_currency_field = $form_state->get('num_countries');
    $add_button = $country_currency_field + 1;
    $form_state->set('num_countries', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $country_currency_field = $form_state->get('num_countries');
    if ($country_currency_field > 0) {
      $remove_button = $country_currency_field - 1;
      $form_state->set('num_countries', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $vars = [
      'currency' => null,
      'language' => null,
      'billing_country' => null,
      'email' => null,
      'ip_country' => null,
      'merchant_id' => null,
      'failed_transactions' => null,
      'depth' => null,
      'suppliers' => [],
    ];
    $condition = $form_state->getValue($form['salesforce_fieldset']['order_verification_condition']['#parents']);
    try {
      $this->expressionLanguage->evaluate($condition, $vars);
    }
    // TODO Make error message smarter
    catch (\Exception $exception) {
      $form_state->setError($form['salesforce_fieldset']['order_verification_condition'], $exception->getMessage());
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('store.settings');

    // Global site currency.
    $global_currency = $form_state->getValue($form['global_currency_fieldset']['global_currency']['#parents']);
    $config->set('global_currency', $global_currency);

    // Country currency.
    $country_currency = $form_state->getValue($form['country_currency_fieldset']['country_currency']['#parents']);
    if (!empty($country_currency)) {
      $config->set('country_currency', $country_currency);
    }

    // Visible currencies.
    $visible_currencies = $form_state->getValue($form['visible_currencies_fieldset']['visible_currencies']['#parents']);
    if (!empty($visible_currencies)) {
      $config->set('visible_currencies', $visible_currencies);
    }

    // Salesforce.
    $opportunityId = $form_state->getValue($form['salesforce_fieldset']['opportunity_id']['#parents']);
    $config->set('opportunity_id_for_payable_invoices', $opportunityId);

    $condition = $form_state->getValue($form['salesforce_fieldset']['order_verification_condition']['#parents']);
    $config->set('order_verification_condition', $condition);

    $config->save();

    drupal_set_message('Store settings have been updated.');
  }
}