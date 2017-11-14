<?php

namespace Drupal\train_booking\Form\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Url;

class SearchUrlGenerator extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_url_generator';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'train_booking/search-url-generator';
    $form['#tree'] = TRUE;

    $form['main'] = [
      '#markup' => $this->t('Welcome to Search URL Generator!'),
    ];

    $form['form_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose form mode.'),
      '#options' => [
        'basic-mode' => $this->t('One-leg trip'),
        'roundtrip-mode' => $this->t('Round trip'),
        'complex-mode' => $this->t('Multi-leg trip'),
      ],
    ];

    for ($i = 1; $i <= 2; $i++) {

      $form['legs'][$i] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'leg',
            'leg-' . $i,
          ],
          'leg' => 'leg-' . $i,
        ],
      ];

      $form['legs'][$i]['leg'] = [
        '#markup' => $this->t('Leg @number :', ['@number' => $i]),
        '#target_type' => 'station',
      ];

      $departure_station = $this->entityTypeManager->getStorage('station')->load(10);
      $form['legs'][$i]['departure_station'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Departure from'),
        '#target_type' => 'station',
        '#default_value' => $departure_station,
      ];

      $arrival_station = $this->entityTypeManager->getStorage('station')->load(27);
      $form['legs'][$i]['arrival_station'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Arrival to'),
        '#target_type' => 'station',
        '#default_value' => $arrival_station,
      ];

      $form['legs'][$i]['departure_date'] = [
        '#type' => 'date',
        '#title' => $this->t('Departure Date'),
        '#date_date_format' => 'd.m.Y',
      ];
    }

    $form['passengers'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'passengers-field',
        ],
      ],
    ];

    $form['passengers']['adults'] = [
      '#type' => 'select',
      '#title' => $this->t('Adults'),
      '#options' => array_combine(range(1,20), range(1,20)),
    ];

    $form['passengers']['children'] = [
      '#type' => 'select',
      '#title' => $this->t('Children'),
      '#options' => array_combine(range(0,10), range(0,10)),
    ];

    $form['passengers']['children_age'] = [
      '#type' => 'details',
      '#title' => $this->t('Children age'),
      '#open' => false,
    ];

    $num_children = 10;
    for ($i = 0; $i < $num_children; $i++) {
      $form['passengers']['children_age']['children_' . $i] = [
        '#type' => 'select',
        '#title' => $this->t('Child  @age', ['@age' => $i + 1], ['context' => 'Search Form']),
        '#empty_value' => '-None-',
        '#options' => array_combine(range(0,12), range(0,12)),
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
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (empty($values['legs'][1]['departure_station'])) {
      $form_state->setError($form['legs'][1]['departure_station'], $this->t('Field @title is required.',
        ['@title' => $form['legs'][1]['departure_station']['#title']]));
    }
    if (empty($values['legs'][1]['arrival_station'])) {
      $form_state->setError($form['legs'][1]['arrival_station'], $this->t('Field @title is required.',
        ['@title' => $form['legs'][1]['arrival_station']['#title']]));
    }
    if ($values['form_mode'] != 'basic-mode') {
      if (empty($values['legs'][2]['departure_station'])) {
        $form_state->setError($form['legs'][2]['departure_station'], $this->t('Field @title is required.',
          ['@title' => $form['legs'][2]['departure_station']['#title']]));
      }
      if (empty($values['legs'][2]['arrival_station'])) {
        $form_state->setError($form['legs'][2]['arrival_station'], $this->t('Field @title is required.',
          ['@title' => $form['legs'][2]['arrival_station']['#title']]));
      }
    }
    for ($i = 0; $i < $values['passengers']['children']; $i++) {
      if ($values['passengers']['children_age']['children_' . $i] == '-None-') {
        $form_state->setErrorByName('children_age', $this->t('Choose children age', [], ['context' => 'Search Form']));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $parameters = [];
    $values = $form_state->getValues();
    $form_mode = $values['form_mode'];
    $parameters['form-mode'] = $form_mode;
    foreach ($values['legs'] as $leg => $leg_data) {
      foreach ($leg_data as $label => $value) {
        if (!empty($value)) {
          $parameters['legs'][$leg][$label] = $value;
        }
      }
      if ($form_mode == 'basic-mode') break;
    }
    $parameters['passengers']['adults'] = $values['passengers']['adults'];
    if ($values['passengers']['children'] > 0) {
      $parameters['passengers']['children'] = $values['passengers']['children'];
      for ($i = 0; $i < $values['passengers']['children']; $i++) {
        $parameters['passengers']['children_age']['child_' . $i] =  $values['passengers']['children_age']['children_' . $i];
      }
    }
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $url = Url::fromRoute('<front>', [], ['query' => $parameters, 'language' => $language]);
    $url->setAbsolute(true);
    drupal_set_message($url->toString());
  }

}