<?php

namespace Drupal\master\Plugin\FieldFormType;

use Drupal\master\FieldFormTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides a 'RzdPassengerForm'.
 *
 * @FieldFormType(
 *   id = "rzd_passenger_form",
 *   label = @Translation("RU passenger form"),
 * )
 */
class RzdPassengerForm extends FieldFormTypeBase {

  /**
   * @param \Drupal\Core\Locale\CountryManager $countryManager
   */
  protected $countryManager;

  /**
   * The parameter, which indicate complexity of this form.
   *
   * @var bool
   */
  protected static $isComplexity = true;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->countryManager = \Drupal::service('country_manager');
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
    $form['id_number'] = [
      '#type' => 'textfield',
      '#title' => t('Passport number', [], ['context' => 'Passenger Form']),
      '#size' => 60,
      '#maxlength' => 128,
    ];
    $form['citizenship'] = [
      '#type' => 'select',
      '#title' => t('Citizenship', [], ['context' => 'Passenger Form']),
      '#options' => $this->countryManager->getList(),
      '#empty_option' => t(' '),
      '#attributes' => [
        'data-placeholder' => t(' '),
      ],
    ];
    $date = new \DateTime();
    $year = $date->format('Y');
    $form['dob'] = [
      '#type' => 'container',
    ];
    $form['dob']['label'] = [
      '#type' => 'label',
      '#title' => t('Date of birth', [], ['context' => 'Passenger Form']),
    ];
    $form['dob']['dates'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'date-of-birth'
      ]
    ];
    $form['dob']['dates']['day'] = [
      '#type' => 'select',
      '#options' => array_combine(range(1, 31), range(1, 31)),
      '#empty_value' => '',
      '#empty_option' => t(' '),
      '#attributes' => [
        'data-placeholder' => t(' '),
      ],
    ];
    $form['dob']['dates']['month'] = [
      '#type' => 'select',
      '#options' => array_combine(range(1, 12), range(1, 12)),
      '#empty_value' => '',
      '#empty_option' => t(' '),
      '#attributes' => [
        'data-placeholder' => t(' '),
      ],
    ];
    $form['dob']['dates']['year'] = [
      '#type' => 'select',
      '#options' => array_combine(range($year - 100, $year), range($year - 100, $year)),
      '#empty_value' => '',
      '#empty_option' => t(' '),
      '#attributes' => [
        'class' => ['dob-year'],
        'data-placeholder' => t(' '),
      ],
    ];
    $form['gender'] = [
      '#type' => 'select',
      '#title' => t('Gender', [], ['context' => 'Passenger Form']),
      '#options' => array_combine(['male', 'female'], [t('Male', [], ['context' => 'Passenger Form']),
        t('Female', [], ['context' => 'Passenger Form'])]),
      '#empty_value' => '',
      '#empty_option' => t(' '),
      '#attributes' => [
        'data-placeholder' => t(' '),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    foreach ($values as $field => $value) {
      if ($field == 'dob') {
        foreach ($value as $dob_fields) {
          foreach ($dob_fields as $dob_field) {
            if (empty($dob_field)) {
              $title = $form['dob']['label']['#title'];
              $form_state->setError($form['dob'], t('Field @title is required.', ['@title' => $title], ['context' => 'Passenger Form']));
            }
          }
        }
      }
      else {
        if (empty($value)) {
          $form_state->setError($form[$field],
            t('Field @title is required.', ['@title' => $form[$field]['#title']], ['context' => 'Passenger Form']));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $birth_dates = $values['dob']['dates'];
    $values['dob'] = DrupalDateTime::createFromArray([
      'year' => $birth_dates['year'], 'month' => $birth_dates['month'], 'day' => $birth_dates['day']
    ]);

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isComplexForm() {
    return static::$isComplexity;
  }
}
