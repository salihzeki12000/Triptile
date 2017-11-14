<?php

namespace Drupal\payment\Form\Admin;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class MerchantRouterRuleForm.
 *
 * @package Drupal\payment\Form
 */
class MerchantRouterRuleForm extends EntityForm {

  /**
   * @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage
   */
  protected $expressionLanguage;

  /**
   * MerchantRouterRuleForm constructor.
   *
   * @param \Symfony\Component\ExpressionLanguage\ExpressionLanguage $expression_language
   */
  public function __construct(ExpressionLanguage $expression_language) {
    $this->expressionLanguage = $expression_language;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('master.expression_language'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\payment\Entity\MerchantRouterRule $merchantRouterRule */
    $merchantRouterRule = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The rule name'),
      '#maxlength' => 255,
      '#default_value' => $merchantRouterRule->label(),
      '#description' => $this->t("Label for the Merchant router rule."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $merchantRouterRule->id(),
      '#machine_name' => [
        'exists' => '\Drupal\payment\Entity\MerchantRouterRule::load',
      ],
      '#disabled' => !$merchantRouterRule->isNew(),
    ];

    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#default_value' => $merchantRouterRule->getWeight(),
    ];

    // TODO Add ability to execute condition with custom params.
    $form['condition'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Condition'),
      '#default_value' => $merchantRouterRule->getCondition(),
      '#required' => TRUE,
      '#description' => $this->t('Next variables are available: invoice_currency, user_currency, ip_country, billing_country, card_type, payment_method.'),
    ];

    $form['merchants'] = array(
      '#type' => 'table',
      '#header' => [$this->t('Merchant'), $this->t('Weight'), $this->t('Enable')],
      '#attributes' => array(
        'id' => 'merchant-router-rule-merchants-table',
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'merchant-weight',
        ),
      ),
    );

    $enabledMerchants = $merchantRouterRule->getMerchantIds();
    $defaultWeight = count($enabledMerchants);
    $merchants = [];
    foreach ($this->entityTypeManager->getStorage('merchant')->loadMultiple() as $merchant) {
      $merchantWeight = array_search($merchant->id(), $enabledMerchants) !== false ? array_search($merchant->id(), $enabledMerchants) : $defaultWeight;
      $merchants[$merchantWeight] = $merchant;
      $defaultWeight = $defaultWeight <= $merchantWeight ? $merchantWeight + 1 : $defaultWeight;
    }
    ksort($merchants);
    foreach ($merchants as $merchantWeight => $merchant) {
      $weight = [
        '#type' => 'textfield',
        '#title' => $this->t('Weight for @title', ['@title' => $merchant->label()]),
        '#title_display' => 'invisible',
        '#default_value' => $merchantWeight,
        '#size' => 3,
        '#attributes' => ['class' => ['merchant-weight']],
      ];
      $enable = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable @title', ['@title' => $merchant->label()]),
        '#title_display' => 'invisible',
        '#default_value' => array_search($merchant->id(), $enabledMerchants) !== false ? true : false,
        '#attributes' => ['class' => ['merchant-enable']],
      ];
      $label = [
        '#plain_text' => $merchant->label(),
      ];
      $form['merchants'][$merchant->id()] = [
        '#attributes' => ['class' => ['draggable']],
        'label' => $label,
        'weight' => $weight,
        'enable' => $enable,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $expressionLanguage = new ExpressionLanguage();
    $vars = [
      'invoice_currency' => 'USD',
      'user_currency' => 'EUR',
      'ip_country' => 'LT',
      'billing_country' => 'LT',
      'card_type' => 'visa',
      'payment_method' => 'credit_card',
    ];
    try {
      $expressionLanguage->evaluate($form_state->getValues()['condition'], $vars);
    }
    catch (\Exception $exception) {
      $form_state->setError($form['condition'], $exception->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\payment\Entity\MerchantRouterRule $merchantRouterRule */
    $merchantRouterRule = $this->entity;
    $enabledMerchants = [];
    $values = $form_state->getValues()['merchants'];
    uasort($values, function($a, $b)  {
      if ($a['weight'] < $b['weight']) {
        return -1;
      }
      elseif ($a['weight'] > $b['weight']) {
        return 1;
      }
      return 0;
    });
    foreach ($values as $merchant_id => $data) {
      if ($data['enable']) {
        $enabledMerchants[] = $merchant_id;
      }
    }
    $merchantRouterRule->setMerchantIds($enabledMerchants);
    $status = $merchantRouterRule->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Merchant router rule.', [
          '%label' => $merchantRouterRule->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Merchant router rule.', [
          '%label' => $merchantRouterRule->label(),
        ]));
    }
    $form_state->setRedirectUrl($merchantRouterRule->toUrl('collection'));
  }

}
