<?php

namespace Drupal\store\Form\Admin;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\currency\FormHelper;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class PriceRuleForm.
 *
 * @package Drupal\store\Form
 */
class PriceRuleForm extends EntityForm {

  /**
   * The currency form helper.
   *
   * @var \Drupal\currency\FormHelper;
   */
  protected $currencyFormHelper;

  /**
   * @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage
   */
  protected $expressionLanguage;

  /**
   * @param \Drupal\currency\FormHelper $currency_form_helper
   * @param \Symfony\Component\ExpressionLanguage\ExpressionLanguage $expression_language
   */
  public function __construct(FormHelper $currency_form_helper, ExpressionLanguage $expression_language) {
    $this->currencyFormHelper = $currency_form_helper;
    $this->expressionLanguage = $expression_language;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('currency.form_helper'), $container->get('master.expression_language'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\store\Entity\PriceRule $price_rule */
    $price_rule = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $price_rule->label(),
      '#description' => $this->t("Name for the price rule."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $price_rule->id(),
      '#machine_name' => [
        'exists' => '\Drupal\store\Entity\PriceRule::load',
      ],
      '#disabled' => !$price_rule->isNew(),
    ];

    $form['price_rule_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Price rule Type'),
      '#default_value' => $price_rule->getPriceRuleType(),
      '#options' => ['before_display' => $this->t('Before Display'),'ticket' => $this->t('Ticket'), 'order' => $this->t('Order')],
      '#description' => $this->t("What is entity is requesting price rule"),
      '#required' => TRUE,
    ];

    $condition_description = '<p>For "before display" type next variables are available: supplier, train, order_depth.</p>';
    $condition_description .= '<p>For "ticket" type next variables are available: supplier, age.</p>';
    $condition_description .= '<p>For "order" type next variables are available: order_depth.</p>';
    $condition_description .= '<p>Example: order_depth >= 11 and order_depth <= 20 and supplier != \'IT\'</p>';

    $form['condition'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Condition'),
      '#default_value' => $price_rule->getCondition(),
      '#description' => $this->t($condition_description),
    ];

    $form['tax_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tax Type'),
      '#default_value' => $price_rule->getTaxType(),
      '#options' => ['rate' => $this->t('Rate'), 'fixed' => $this->t('Fixed')],
      '#description' => $this->t("Math action with the price."),
      '#required' => TRUE,
    ];

    $form['tax_value'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#title' => $this->t('Tax Value'),
      '#default_value' => $price_rule->getTaxValue(),
      '#description' => $this->t("The number, which will be change the price."),
      '#required' => TRUE,
    ];

    $currency_options = $this->currencyFormHelper->getCurrencyOptions();
    // @todo will delete on production.
    unset($currency_options['XXX']);
    $form['tax_value_currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Tax Value Currency'),
      "#empty_option" => $this->t('- Select -'),
      '#default_value' => $price_rule->getTaxValueCurrency(),
      '#options' => $currency_options,
      '#description' => $this->t("Choose currency for Tax Value."),
      '#states' => [
        'disabled' => [
          [
            'select[name="[tax_type]"]' => [
              ['value' => 'rate'],
            ],
          ],
        ],
      ],
    ];

    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#default_value' => $price_rule->getWeight() ? $price_rule->getWeight() : 0,
      '#description' => $this->t("The weight of price rule."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    switch ($values['price_rule_type']) {
      case 'before_display':
        $vars = [
          'supplier' => 'E3',
          'train' => '1',
          'order_depth' => 1,
        ];
        break;
      case 'ticket':
        $vars = [
          'supplier' => 'E3',
          'age' => 18,
        ];
        break;
      case 'order':
        $vars = [
          'order_depth' => 1,
        ];
        break;
      default:
        $vars = [];
    }
    try {
      $this->expressionLanguage->evaluate($values['condition'], $vars);
    }
    catch (\Exception $exception) {
      $form_state->setError($form['condition'], $exception->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $price_rule = $this->entity;
    $status = $price_rule->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label price rule.', [
          '%label' => $price_rule->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label price rule.', [
          '%label' => $price_rule->label(),
        ]));
    }
    $form_state->setRedirectUrl($price_rule->urlInfo('collection'));
  }

}
