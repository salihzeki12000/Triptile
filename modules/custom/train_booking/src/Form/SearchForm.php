<?php

namespace Drupal\train_booking\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;

/**
 * Class SearchForm.
 *
 * @package Drupal\train_booking\Form
 */
class SearchForm extends TrainBookingBaseForm  {

  /**
   * The type of form.
   */
  protected $formMode;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'train_booking_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $picked_stations = $leg_data = $passengers_data = [];

    $this->formMode = 'basic-mode';
    // If we have GET parameters in the url, so need to use it.
    $request = $this->getRequest();
    $departureStation = $this->getStationByName($request->get('from'));
    $arrivalStation = $this->getStationByName($request->get('to'));
    if ($departureStation && $arrivalStation) {
      $leg_data[1]['departure_station'] = $departureStation;
      $leg_data[1]['arrival_station'] = $arrivalStation;
      $picked_stations = [
        $departureStation => $departureStation,
        $arrivalStation => $arrivalStation
      ];
    }
    if (!empty($request->get('legs'))) {
      $data = $this->getDataFromUrl($request, $form_state);
      $leg_data = $data['leg_data'];
      $passengers_data = $data['passengers_data'];
      $picked_stations = $data['picked_stations'];
      if ($this->isValidDataForRedirect($data['form_mode'], $leg_data, $passengers_data)) {
        $store_data = $this->submitDataFromUrlToStore($data['form_mode'], $data['leg_data'], $data['passengers_data']);
        try {
          $this->store->getSessionExpirationTime();
          $this->store = $this->sessionStoreFactory->get($this->getCollectionName());
        }
        catch (\Exception $e) {
        }
        $this->store->setSessionExpirationTime(REQUEST_TIME + static::SESSION_LIFE_TIME);
        $this->saveSearchResult($store_data);
        $this->store->set('search_request', $store_data);
        $this->userPrivateTempStore->set('search_request', $store_data);
        return $this->redirect('train_booking.timetable_form', ['session_id' => $this->store->getSessionId()]);
      }
    }
    else {
      $node =  $request->get('node');
      /* @var \Drupal\node\Entity\Node $node */
      if(!empty($node) && $node->getType() == 'route_page') {
        /** @var \Drupal\train_base\Entity\Station $departureStation */
        /** @var \Drupal\train_base\Entity\Station $arrivalStation */
        if (($departureStation = $node->get('departure_station')->entity)
          && ($arrivalStation = $node->get('arrival_station')->entity)) {
          $leg_data[1]['departure_station'] = $departureStation->id();
          $leg_data[1]['arrival_station'] = $arrivalStation->id();
          $picked_stations = [
            $departureStation->id() => $departureStation->id(),
            $arrivalStation->id() => $arrivalStation->id(),
          ];
        }
      }
    }

    // But if we have some results in the store - need to use it (Example: Timetable page).
    $search_request = NULL;
    $session_id = $this->getRequest()->attributes->get('session_id');
    if ($session_id) {
      $this->store->setSessionId($session_id);
      $search_request = $this->store->get('search_request');
      if (isset($search_request)) {
        foreach ($search_request['legs'] as $leg => $legData) {
          $leg_data[$leg]['departure_station'] = $legData['departure_station'];
          $leg_data[$leg]['arrival_station'] = $legData['arrival_station'];
          $leg_data[$leg]['departure_date'] = $legData['departure_date']->format('d.m.Y');
          $picked_stations = [
            $legData['departure_station'] => $legData['departure_station'],
            $legData['arrival_station'] => $legData['arrival_station'],
          ];
        }
        if (isset($search_request['round_trip']) && $search_request['round_trip'] == true) {
          $form_state->set('roundtrip', true);
          $this->formMode = 'roundtrip-mode';
        }
        $passengers_data['adults_number'] = isset($search_request['adults']) ? $search_request['adults'] : NULL;
        $passengers_data['children_number'] = isset($search_request['children']) ? $search_request['children'] : NULL;
        $passengers_data['children_age'] = isset($search_request['children_age']) ? $search_request['children_age'] : [];
      }
    }


    $trainBookingConfig = $this->configFactory->get('train_booking.settings');
    $trainProviderConfig = $this->configFactory->get('train_provider.settings');

    // Fill options of stations by popular stations and user popular stations.
    $popular_stations = $trainBookingConfig->get('popular_stations') ? : [];
    $user_popular_stations = $this->getUserPopularStations();
    $picked_stations = array_merge($picked_stations, $user_popular_stations, $popular_stations);
    $station_options = $this->getStationOptions($picked_stations);

    // Get minimum days before departure from config (some buffer for RTN)
    $min_days_before_departure = $trainProviderConfig->get('common_min_days_before_departure');

    $form['#attached']['library'][] = 'train_booking/search-form';
    $form['#attached']['drupalSettings']['min_days_before_departure'] = $min_days_before_departure;
    $form['#attached']['library'][] = 'train_booking/move-multileg-popup';

    if ($is_front = \Drupal::service('path.matcher')->isFrontPage()) {
      $form['#attached']['library'][] = 'train_booking/scroll-form';
    }

    $form['#tree'] = TRUE;
    $form['#cache'] = FALSE;

    $form['form_switcher'] = [
      '#theme' => 'search_forms_switcher',
      '#roundtrip' => $form_state->get('roundtrip'),
      '#pre_render' => [
        [$this, 'preRenderFromSwitcher'],
      ],
    ];

    $form['basic_mode'] = $this->getBasicModeForm($form_state, $station_options, $leg_data, $passengers_data);
    $form['roundtrip_mode'] = $this->getRoundtripModeForm($form_state, $station_options, $leg_data, $passengers_data);
    $form['complex_mode'] = $this->getComplexModeForm($form_state, $station_options, $leg_data, $passengers_data, 2);

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $submit = $form_state->getTriggeringElement();
    if ($submit['#name'] == 'basic-mode-submit') {
      $this->formMode = 'basic-mode';
      $this->validateBasicModeForm($form['basic_mode'], $form_state);
    }
    elseif ($submit['#name'] == 'roundtrip-mode-submit') {
      $this->formMode = 'roundtrip-mode';
      $this->validateRoundTripModeForm($form['roundtrip_mode'], $form_state);
    }
    elseif ($submit['#name'] = 'complex-mode-submit') {
      $this->validateComplexModeForm($form['complex_mode'], $form_state);
    }
  }

  protected function validateBasicModeForm(&$form, FormStateInterface $form_state) {
    $this->searchFormBaseValidation($form, $form_state);
  }

  protected function validateRoundTripModeForm(&$form, FormStateInterface $form_state) {
    $this->searchFormBaseValidation($form, $form_state);
  }

  protected function validateComplexModeForm(&$form, FormStateInterface $form_state) {
    $this->searchFormBaseValidation($form, $form_state);
  }

  protected function searchFormBaseValidation(&$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    foreach ($values['legs'] as $leg => $data) {
      $stations_wrapper = &$form['legs'][$leg]['stations_wrapper'];
      if (empty($data['stations_wrapper']['departure_station'])) {
        $form_state->setError($stations_wrapper['departure_station'], $this->t('Field @title is required.',
          ['@title' => $stations_wrapper['departure_station']['#settings']['placeholder']], ['context' => 'Search Form']));
      }
      if (empty($data['stations_wrapper']['arrival_station'])) {
        $form_state->setError($stations_wrapper['arrival_station'], $this->t('Field @title is required.',
          ['@title' => $stations_wrapper['arrival_station']['#settings']['placeholder']], ['context' => 'Search Form']));
      }
      if (empty($data['travel_date_wrapper']['travel_date']['departure_date_input'])) {
        $form_state->setErrorByName('travel_date', $this->t('Choose travel date', [], ['context' => 'Search Form']));
      }
    }
    if (empty($values['passengers']['passengers_field']['wrapper']['adults']) || $values['passengers']['passengers_field']['wrapper']['adults'] == 0) {
      $form_state->setError($form['passengers']['passengers_field']['wrapper']['adults'], $this->t('Field @title is required.',
        ['@title' => $form['passengers']['passengers_field']['wrapper']['adults']['#title']], ['context' => 'Search Form']));
    }
    for ($i = 0; $i < $values['passengers']['passengers_field']['wrapper']['children']['children_number']; $i++) {
      if (empty($values['passengers']['passengers_field']['wrapper']['children']['children_age']['children_' . $i])) {
        $form_state->setErrorByName('children_age', $this->t('Choose children age', [], ['context' => 'Search Form']));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->store->getSessionExpirationTime();
      $this->store = $this->sessionStoreFactory->get($this->getCollectionName());
    }
    catch (\Exception $e) {
    }
    $store_data = [];
    $this->store->setSessionExpirationTime(REQUEST_TIME + static::SESSION_LIFE_TIME);
    $submit = $form_state->getTriggeringElement();
    if ($submit['#name'] == 'basic-mode-submit') {
      $store_data = $this->submitBasicModeForm($form['basic_mode'], $form_state);
    }
    elseif ($submit['#name'] == 'roundtrip-mode-submit') {
      $store_data = $this->submitRoundTripModeForm($form['roundtrip_mode'], $form_state);
    }
    elseif ($submit['#name'] == 'complex-mode-submit') {
      $store_data = $this->submitComplexModeForm($form['complex_mode'], $form_state);
    }
    $this->saveSearchResult($store_data);
    $this->store->set('search_request', $store_data);
    $this->userPrivateTempStore->set('search_request', $store_data);
    $session_id = $this->store->getSessionId();
    $this->trainBookingLogger->logSearchForm($session_id, $store_data, $this->store->get('search_result'));
    $form_state->setRedirect('train_booking.timetable_form', ['session_id' => $session_id]);
  }

  protected function submitBasicModeForm(&$form, FormStateInterface $form_state) {
    $store_data = $this->searchFormBaseSubmit($form, $form_state);
    $store_data['round_trip'] = false;
    $store_data['complex_trip'] = false;

    return $store_data;
  }

  protected function submitRoundTripModeForm(&$form, FormStateInterface $form_state) {
    $store_data = $this->searchFormBaseSubmit($form, $form_state);
    $store_data['round_trip'] = true;
    $store_data['complex_trip'] = true;

    return $store_data;
  }

  protected function submitComplexModeForm(&$form, FormStateInterface $form_state) {
    $store_data = $this->searchFormBaseSubmit($form, $form_state);
    $store_data['round_trip'] = false;
    $store_data['complex_trip'] = true;

    return $store_data;
  }

  protected function searchFormBaseSubmit(&$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    foreach ($values['legs'] as $leg => $data) {
      $this->updateUserPopularStations([$data['stations_wrapper']['departure_station'], $data['stations_wrapper']['arrival_station']]);
      $store_data['legs'][$leg]['departure_station'] = $data['stations_wrapper']['departure_station'];
      $store_data['legs'][$leg]['arrival_station'] = $data['stations_wrapper']['arrival_station'];
      $departureStation = $this->loadEntity('station', $data['stations_wrapper']['departure_station']);
      $date = explode('.', $data['travel_date_wrapper']['travel_date']['departure_date_input']);
      $store_data['legs'][$leg]['departure_date'] = DrupalDateTime::createFromArray([
        'year' => $date[2],
        'month' => $date[1],
        'day' => $date[0]
      ], $departureStation->getTimezone());
    }
    $store_data['adults'] = $values['passengers']['passengers_field']['wrapper']['adults'];
    $store_data['children'] = $values['passengers']['passengers_field']['wrapper']['children']['children_number'];
    for ($i = 1; $i <= $values['passengers']['passengers_field']['wrapper']['adults']; $i++) {
      $store_data['pax']['passenger_' . $i] = 30;
    }
    for ($j = 0; $j < $values['passengers']['passengers_field']['wrapper']['children']['children_number']; $j++) {
      $store_data['children_age'][] = $values['passengers']['passengers_field']['wrapper']['children']['children_age']['children_' . $j];
      $store_data['pax']['passenger_' . $i] = $values['passengers']['passengers_field']['wrapper']['children']['children_age']['children_' . $j];
      $i++;
    }

    return $store_data;
  }

  public function preRenderSomeModeForm($element) {
    $form_mode = $this->formMode;
    if ($element['#attributes']['form-mode'] == $form_mode) {
      if (!in_array('visible', $element['#attributes']['class'])) {
        $element['#attributes']['class'][] = 'visible';
      }
    }
    else {
      if ($key = array_search('visible', $element['#attributes']['class'])) {
        unset($element['#attributes']['class'][$key]);
      }
    }

    return $element;
  }

  public function preRenderFromSwitcher($element) {
    $form_mode = $this->formMode;
    if ($form_mode == 'roundtrip-mode') {
      $element['#roundtrip'] = true;
    }

    return $element;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $station_options
   * @param $leg_data
   * @param $passengers_data
   * @return array|mixed
   */
  protected function getBasicModeForm(FormStateInterface $form_state, $station_options, $leg_data, $passengers_data) {
    $form = [
      '#type' => 'container',
      '#pre_render' => [
        [$this, 'preRenderSomeModeForm'],
      ],
      '#attributes' => [
        'class' => [
          'form-flex-container',
          'basic-mode',
        ],
        'form-mode' => 'basic-mode',
      ],
    ];

    if (!$form_state->get('roundtrip')) {
      $form['#attributes']['class'][] = 'visible';
    }

    $form['legs'] = $this->getLegDataForm('basic-mode', $station_options, $leg_data, 1);
    $form['passengers'] = $this->getPassengersForm($passengers_data);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit', [], ['context' => 'Search Form']),
      '#button_type' => 'primary',
      '#weight' => 100,
      '#name' => 'basic-mode-submit',
    ];

    return $form;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $station_options
   * @param $leg_data
   * @param $passengers_data
   * @return array
   */
  protected function getRoundtripModeForm(FormStateInterface $form_state, $station_options, $leg_data, $passengers_data) {
    $form = [
      '#type' => 'container',
      '#pre_render' => [
        [$this, 'preRenderSomeModeForm'],
      ],
      '#attributes' => [
        'class' => [
          'form-flex-container',
          'roundtrip-mode',
        ],
        'form-mode' => 'roundtrip-mode',
      ],
    ];

    if ($form_state->get('roundtrip')) {
      $form['#attributes']['class'][] = 'visible';
    }

    $form['legs'] = $this->getLegDataForm('roundtrip-mode', $station_options, $leg_data, 2);
    $form['passengers'] = $this->getPassengersForm($passengers_data);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit', [], ['context' => 'Search Form']),
      '#button_type' => 'primary',
      '#weight' => 100,
      '#name' => 'roundtrip-mode-submit',
    ];

    return $form;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $station_options
   * @param $leg_data
   * @param $passengers_data
   * @param $count_of_legs
   * @return array
   */
  protected function getComplexModeForm(FormStateInterface $form_state, $station_options, $leg_data, $passengers_data, $count_of_legs) {
    $form = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'form-flex-container',
          'complex-mode',
        ],
        'form-mode' => 'complex-mode',
      ],
    ];

    $form['legs'] = $this->getLegDataForm('complex-mode', $station_options, $leg_data, $count_of_legs);
    $form['passengers'] = $this->getPassengersForm($passengers_data);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit', [], ['context' => 'Search Form']),
      '#button_type' => 'primary',
      '#weight' => 100,
      '#name' => 'complex-mode-submit',
    ];

    return $form;
  }


  /**
   * Generate leg data (stations, travel dates) for different form modes.
   *
   * @param $form_mode
   * @param $station_options
   * @param $leg_data
   * @param $count_of_leg
   * @return array
   */
  protected function getLegDataForm($form_mode, $station_options, $leg_data, $count_of_leg) {

    $form = [];

    for ($i = 1; $i <= $count_of_leg; $i++) {
      if(!empty($leg_data[$i])) {
        $departureStation = !empty($leg_data[$i]['departure_station']) ? $leg_data[$i]['departure_station'] : null;
        $arrivalStation = !empty($leg_data[$i]['arrival_station']) ? $leg_data[$i]['arrival_station'] : null;
        $departureDate = !empty($leg_data[$i]['departure_date']) ? $leg_data[$i]['departure_date'] : null;
      }

      $form[$i] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'leg',
            'leg-' . $i,
          ],
          'leg' => 'leg-' . $i,
        ],
      ];

      $form[$i]['stations_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'stations-wrapper',
          ],
        ],
      ];

      $form[$i]['stations_wrapper']['departure_station'] = [
        '#type' => 'selectize',
        '#title' => $this->t('Departure from', [], ['context' => 'Search form label']),
        '#default_value' => isset($departureStation) ? $departureStation : NULL,
        '#weight' => 0,
        '#settings' => [
          'options' => $station_options,
          // @todo make it more beautiful.
          'ajaxUrl' => Url::fromRoute('train_booking.get_stations')
              ->toString() . '/',
          'placeholder' => $this->t('From', [], ['context' => 'Search form placeholder']),

        ],
        '#attributes' => [
          'class' => [
            'leg-' . $i,
            'station',
            'departure-station',
            'chosen-disable',
          ],
          'leg' => 'leg-' . $i,
          'station' => 'departure-station',
        ],
        '#options_callback' => [$this, 'getStationOptions'],
      ];

      $form[$i]['stations_wrapper']['arrival_station'] = [
        '#type' => 'selectize',
        '#title' => $this->t('Arrival to', [], ['context' => 'Search form label']),
        '#default_value' => isset($arrivalStation) ? $arrivalStation : NULL,
        '#weight' => 2,
        '#settings' => [
          'options' => $station_options,
          // @todo make it more beautiful.
          'ajaxUrl' => Url::fromRoute('train_booking.get_stations')
              ->toString() . '/',
          'placeholder' => $this->t('To', [], ['context' => 'Search form placeholder']),
        ],
        '#attributes' => [
          'class' => [
            'leg-' . $i,
            'station',
            'arrival-station',
            'chosen-disable',
          ],
          'leg' => 'leg-' . $i,
          'station' => 'arrival-station',
        ],
        '#options_callback' => [$this, 'getStationOptions'],
      ];

      if ($form_mode == 'complex-mode') {
        $form[$i]['stations_wrapper']['departure_station']['#attributes']['tabindex'] = -1;
        $form[$i]['stations_wrapper']['arrival_station']['#attributes']['tabindex'] = -1;
      }

      $form[$i]['travel_date_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'travel-date-element-wrapper',
            'leg-' . $i,
          ],
        ],
      ];

      $form[$i]['travel_date_wrapper']['label'] = [
        '#title' => '<label>' . t('Date', [], ['context' => 'Search form label']) . '</label>',
        '#type' => 'label',
        '#attributes' => [
          'class' => [
            'leg-' . $i,
            'travel-date-label',
          ],
          'leg' => 'leg-' . $i,
        ],
      ];

      $form[$i]['travel_date_wrapper']['travel_date'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'travel-date-wrapper',
            'leg-' . $i,
          ],
        ],
      ];

      $form[$i]['travel_date_wrapper']['travel_date']['input'] = [
        '#markup' => t('Travel date', [], ['context' => 'Search form placeholder']),
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'leg-' . $i,
            'travel-date-input',
          ],
          'leg' => 'leg-' . $i,
        ],
      ];

      $form[$i]['travel_date_wrapper']['travel_date']['departure_date'] = [
        '#type' => 'container',
        '#attributes' => [
          'default-value' => isset($departureDate) ? $departureDate : NULL,
          'class' => [
            'leg-' . $i,
            'datepicker-element',
            'departure-date',
            'notranslate',
          ],
          'leg' => 'leg-' . $i,
        ],
      ];

      $form[$i]['travel_date_wrapper']['travel_date']['departure_date_input'] = [
        '#type' => 'hidden',
        '#default_value' => isset($departureDate) ? $departureDate : NULL,
        '#attributes' => [
          'class' => [
            'leg-' . $i,
            'departure-date',
          ],
          'leg' => 'leg-' . $i,
        ],
      ];
    }

    return $form;
  }

  protected function getPassengersForm($data) {
    extract($data);

    $form = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'passengers-field-wrapper',
        ],
      ],
    ];

    $form['label'] = [
      '#title' => '<label>' . t('Passengers', [], ['context' => 'Search form label']) . '</label>',
      '#type' => 'label',
      '#attributes' => [
        'class' => [
          'passengers-field-label',
        ],
      ],
    ];

    $form['passengers_field'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'passengers-field',
        ],
      ],
    ];
    $form['passengers_field']['number'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'passengers-number',
        ],
      ],
    ];
    $form['passengers_field']['number']['value'] = [
      '#type' => 'container',
      '#markup' => $this->formatPlural(1, '1 passenger', '@count passengers', [], ['context' => 'Search Form']),
      '#attributes' => [
        'class' => [
          'value',
        ],
      ],
    ];
    $form['passengers_field']['number']['arrow'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'arrow',
        ],
      ],
    ];
    $form['passengers_field']['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'passengers-wrapper',
        ],
      ],
    ];
    $form['passengers_field']['wrapper']['adults'] = [
      '#type' => 'spinner',
      '#title' => $this->t('Adults', [], ['context' => 'Search Form']),
      '#settings' => [
        'min' => 1,
        'defaultValue' => isset($adults_number) ? $adults_number : 1,
      ],
      '#attributes' => [
        'class' => [
          'adults-number',
        ],
      ],
    ];
    $form['passengers_field']['wrapper']['children'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'children-wrapper',
        ],
      ],
    ];
    $form['passengers_field']['wrapper']['children']['children_number'] = [
      '#type' => 'spinner',
      '#title' => $this->t('Children', [], ['context' => 'Search Form']),
      '#settings' => [
        'defaultValue' => isset($children_number) ? $children_number : 0,
      ],
      '#attributes' => [
        'class' => [
          'children-number',
        ],
      ],
    ];
    $form['passengers_field']['wrapper']['children']['children_age'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'children-age-wrapper',
        ],
      ],
    ];
    $num_children = 10;
    for ($i = 0; $i < $num_children; $i++) {
      $form['passengers_field']['wrapper']['children']['children_age']['children_' . $i] = [
        '#type' => 'select',
        '#title' => $this->t('Child  @age', ['@age' => $i + 1], ['context' => 'Search Form']),
        '#default_value' => isset($children_age[$i]) ? $children_age[$i] : NULL,
        '#options' => array_combine(range(0,12), range(0,12)),
        '#empty_option' => $this->t('Age', [], ['context' => 'Search Form']),
        '#attributes' => [
          'class' => [
            'children',
            'child-' . $i,
            $i,
          ],
          'child' => $i,
        ],
      ];
    }

    return $form;
  }

  /**
   * Generates options list for Station select list.
   *
   * @param array $picked_stations
   * @return array
   */
  public function getStationOptions(array $picked_stations = []) {
    $station_options = $entity_ids = [];
    // @todo uncomment if need to load some stations from DB.
    /*$query = $this->entityQuery->get('station');
    $query->condition('status', 1);
    $query->notExists('parent_station');
    $query->range(0, 2);
    $entity_ids = $query->execute();*/
    $entity_ids += $picked_stations;
    if ($entity_ids) {
      $stations = $this->entityTypeManager->getStorage('station')->loadMultiple($entity_ids);
      /** @var \Drupal\train_base\Entity\Station $station */
      foreach ($stations as $station) {
        $station_options[$station->id()] = $station->getName();
      }
    }

    return $station_options;
  }

  /**
   * @param string $name
   * @return int|null
   */
  protected function getStationByName($name) {
    $station = null;
    $query = $this->entityQuery->get('station');
    $query->condition('status', 1);
    $query->condition('name', $name);
    $query->notExists('parent_station');
    $entity_ids = $query->execute();
    if ($entity_ids) {
      $stations = $this->entityTypeManager->getStorage('station')->loadMultiple($entity_ids);
      /** @var \Drupal\train_base\Entity\Station $station */
      foreach ($stations as $station) {
        return $station->id();
      }
    }
  }

  protected function getDataFromUrl($request, $form_state) {
    $leg_data = $passengers_data = $picked_stations = [];
    switch ($request->get('form-mode')) {
      case 'basic-mode':
        $form_mode = 'basic-mode';
        break;
      case 'roundtrip-mode':
        $form_mode = 'roundtrip-mode';
        $this->formMode = 'roundtrip-mode';
        $form_state->set('roundtrip', true);
        break;
      case 'complex-mode':
        $form_mode = 'complex-mode';
        break;
      default:
        $form_mode = 'basic-mode';
    }
    foreach ($request->get('legs') as $leg => $data) {
      if (!empty($data['departure_station'])) {
        $leg_data[$leg]['departure_station'] = $data['departure_station'];
        $picked_stations[$data['departure_station']] = $data['departure_station'];
      }
      if (!empty($data['arrival_station'])) {
        $leg_data[$leg]['arrival_station'] = $data['arrival_station'];
        $picked_stations[$data['arrival_station']] = $data['arrival_station'];
      }
      if (!empty($data['departure_date'])) {
        $departureDate = new DrupalDateTime($data['departure_date']);
        $leg_data[$leg]['departure_date'] = $departureDate->format('d.m.Y');
      }
    }
    if ($passengers = $request->get('passengers')) {
      if (!empty($passengers['adults'])) {
        $passengers_data['adults_number'] = $passengers['adults'];
      }
      if (!empty($passengers['children'])) {
        $passengers_data['children_number'] = $passengers['children'];
        $children_age = [];
        foreach ($passengers['children_age'] as $age) {
          $children_age[] = $age;
        }
        $passengers_data['children_age'] = $children_age;
      }
      else {
        $passengers_data['children_number'] = 0;
      }
    }

    return ['form_mode' => $form_mode, 'leg_data' => $leg_data, 'passengers_data' => $passengers_data, 'picked_stations' => $picked_stations];
  }

  protected function isValidDataForRedirect($form_mode, $leg_data, $passengers_data) {
    if ($form_mode != 'basic-mode' && count($leg_data) < 2) {
      return false;
    }
    foreach ($leg_data as $leg => $data) {
      if (empty($data['departure_station'])) {
        return false;
      }
      if (empty($data['arrival_station'])) {
        return false;
      }
      if (empty($data['departure_date'])) {
        return false;
      }
    }
    if (!empty($passengers_data['children'])) {
      for ($i = 0; $i < $passengers_data['children']; $i++) {
        if (empty($passengers_data['children_age']['child_' . $i])) {
          return false;
        }
      }
    }

    return true;
  }

  protected function submitDataFromUrlToStore($form_mode, $leg_data, $passengers_data) {
    if ($form_mode == 'roundtrip-mode') {
      $store_data['round_trip'] = true;
      $store_data['complex_trip'] = true;
    }
    elseif ($form_mode == 'complex-mode') {
      $store_data['round_trip'] = false;
      $store_data['complex_trip'] = true;
    }
    elseif ($form_mode == 'basic-mode') {
      $store_data['round_trip'] = false;
      $store_data['complex_trip'] = false;
    }
    foreach ($leg_data as $leg => $data) {
      $store_data['legs'][$leg]['departure_station'] = $data['departure_station'];
      $store_data['legs'][$leg]['arrival_station'] = $data['arrival_station'];
      $departureStation = $this->loadEntity('station', $data['departure_station']);
      $date = explode('.', $data['departure_date']);
      $store_data['legs'][$leg]['departure_date'] = DrupalDateTime::createFromArray([
        'year' => $date[2],
        'month' => $date[1],
        'day' => $date[0]
      ], $departureStation->getTimezone());
    }
    $store_data['adults'] = $passengers_data['adults_number'];
    if (isset($passengers_data['children_number'])) {
      $store_data['children'] = $passengers_data['children_number'];
      for ($i = 1; $i <= $passengers_data['adults_number']; $i++) {
        $store_data['pax']['passenger_' . $i] = 30;
      }
      for ($j = 0; $j < $passengers_data['children_number']; $j++) {
        $store_data['children_age'][] = $passengers_data['children_age'][$j];
        $store_data['pax']['passenger_' . $i] = $passengers_data['children_age'][$j];
        $i++;
      }
    }

    return $store_data;
  }

  /**
   * @param array $store_data
   */
  public function saveSearchResult($store_data) {
    $search_configuration = [
      'adult_number' => $store_data['adults'],
      'child_number' => $store_data['children'],
      'round_trip' => $store_data['round_trip'],
      'complex_trip' => $store_data['complex_trip'],
    ];

    foreach ($store_data['legs'] as $leg => $legData) {
      $search_configuration['legs'][$leg]['departure_station'] = $this->loadEntity('station', $legData['departure_station']);
      $search_configuration['legs'][$leg]['arrival_station'] = $this->loadEntity('station', $legData['arrival_station']);
      $search_configuration['legs'][$leg]['departure_date'] = $legData['departure_date'];
    }

    $search_result = $this->trainSearcher->getTimetable($search_configuration);
    $this->store->set('search_result', $search_result);
  }

}
