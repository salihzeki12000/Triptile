<?php

namespace Drupal\master\Plugin\FieldFormType;

use Drupal\master\FieldFormTypeBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'DeadEasyForm'.
 *
 * @FieldFormType(
 *   id = "dead_easy_form",
 *   label = @Translation("Dead easy form (Passenger Form)"),
 * )
 */
class DeadEasyForm extends FieldFormTypeBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormElements($parameters = []) {
    $form = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'internal-fields-wrapper',
      ],
    ];
    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => t('First name', [], ['context' => 'Passenger Form']),
      '#size' => 60,
      '#maxlength' => 128,
    ];
    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => t('Last name', [], ['context' => 'Passenger Form']),
      '#size' => 60,
      '#maxlength' => 128,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    foreach ($values as $field => $value) {
      if (empty($value)) {
        $form_state->setError($form[$field],
          t('Field @title is required.', ['@title' => $form[$field]['#title']], ['context' => 'Passenger Form']));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    return $form_state->getValue($form['#parents']);
  }

}
