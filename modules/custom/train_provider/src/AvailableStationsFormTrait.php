<?php

namespace Drupal\train_provider;

use Drupal\Core\Form\FormStateInterface;

trait AvailableStationsFormTrait {

  /**
   * Get Available routes form for specific train provider.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function getAvailableStationsSettingsForm(array &$form, FormStateInterface $form_state) {
    $availableRoutes = isset($this->configuration['available_stations']) ?
      $this->configuration['available_stations'] : [];
    $routesNumber = $form_state->get('available_stations_num_routes');
    $form['available_stations_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Available stations'),
      '#prefix' => '<div id="routes-stations-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#tree' => true,
    ];
    if (!isset($routesNumber)) {
      $routesNumber = count($availableRoutes);
      $form_state->set('available_stations_num_routes', $routesNumber);
    }
    $form['available_stations_fieldset']['routes'] = [
      '#type' => 'container',
    ];
    for ($i = 0; $i < $routesNumber; $i++) {
      $form['available_stations_fieldset']['routes'][$i] = [
        '#type' => 'container',
        '#markup' => '#' . ($i + 1),
      ];
      $departureStation = $this->entityTypeManager->getStorage('station')->load($availableRoutes[$i]['departure_station']);
      $arrivalStation = $this->entityTypeManager->getStorage('station')->load($availableRoutes[$i]['arrival_station']);
      $form['available_stations_fieldset']['routes'][$i]['departure_station'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'station',
        '#title' => $this->t('From'),
        '#default_value' => $departureStation,
      ];
      $form['available_stations_fieldset']['routes'][$i]['arrival_station'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'station',
        '#title' => $this->t('To'),
        '#default_value' => $arrivalStation,
      ];
      $form['available_stations_fieldset']['routes'][$i]['stations'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Stations'),
        '#prefix' => '<div id="stations-fieldset-wrapper-' . $i . '">',
        '#suffix' => '</div>',
        '#tree' => true,
      ];
      $form['available_stations_fieldset']['routes'][$i]['stations']['markup'] = [
        '#type' => 'container',
        '#markup' => $this->t('Specific available stations for departure station of this route'),
      ];
      $stationsNumber = $form_state->get('stations_' . $i);
      if (!isset($stationsNumber)) {
        if (isset($availableRoutes[$i]['stations'])) {
          $stationsNumber = count($availableRoutes[$i]['stations']);
        }
        else {
          $stationsNumber = 0;
        }
        $form_state->set('stations_' . $i, $stationsNumber);
      }
      for ($j = 0; $j < $stationsNumber; $j++) {
        if (isset($availableRoutes[$i]['stations'])) {
          $station = $this->entityTypeManager->getStorage('station')
            ->load($availableRoutes[$i]['stations'][$j]['id']);
        }
        else {
          $station = null;
        }
        $form['available_stations_fieldset']['routes'][$i]['stations'][$j]['id'] = [
          '#type' => 'entity_autocomplete',
          '#target_type' => 'station',
          '#title' => $this->t('Station'),
          '#default_value' => $station,
        ];
      }
      $form['available_stations_fieldset']['routes'][$i]['stations']['actions']['add_route'] = [
        '#type' => 'submit',
        '#value' => t('Add one more'),
        '#name' => 'add_one_station_' . $i,
        '#submit' => [self::class . '::addOneStation'],
        '#ajax' => [
          'callback' => self::class . '::addmoreCallbackStation',
          'wrapper' => 'stations-fieldset-wrapper-' . $i,
        ],
      ];
      if ($stationsNumber > 0) {
        $form['available_stations_fieldset']['routes'][$i]['stations']['actions']['remove_route'] = [
          '#type' => 'submit',
          '#value' => t('Remove one'),
          '#name' => 'remove_one_station_' . $i,
          '#submit' => [self::class . '::removeCallbackStation'],
          '#ajax' => [
            'callback' => self::class . '::addmoreCallbackStation',
            'wrapper' => 'stations-fieldset-wrapper-' . $i,
          ],
        ];
      }
    }
    $form['available_stations_fieldset']['actions']['add_route'] = [
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#name' => 'add_one_route',
      '#submit' => [self::class . '::addOneRoute'],
      '#ajax' => [
        'callback' => self::class . '::addmoreCallbackRoute',
        'wrapper' => 'routes-stations-fieldset-wrapper',
      ],
    ];
    if ($routesNumber > 0) {
      $form['available_stations_fieldset']['actions']['remove_route'] = [
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#name' => 'remove_one_route',
        '#submit' => [self::class . '::removeCallbackRoute'],
        '#ajax' => [
          'callback' => self::class . '::addmoreCallbackRoute',
          'wrapper' => 'routes-stations-fieldset-wrapper',
        ],
      ];
    }
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the stations in it.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return mixed
   */
  static public function addmoreCallbackRoute(array &$form, FormStateInterface $form_state) {
    return $form['available_stations_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  static public function addOneRoute(array &$form, FormStateInterface $form_state) {
    $routeField = $form_state->get('available_stations_num_routes');
    $addButton = $routeField + 1;
    $form_state->set('available_stations_num_routes', $addButton);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  static function removeCallbackRoute(array &$form, FormStateInterface $form_state) {
    $routeField = $form_state->get('available_stations_num_routes');
    if ($routeField > 0) {
      $removeButton = $routeField - 1;
      $form_state->set('available_stations_num_routes', $removeButton);
    }
    $form_state->setRebuild();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the stations in it.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return mixed
   */
  static public function addmoreCallbackStation(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    return $form['available_stations_fieldset']['routes'][$button['#parents'][2]]['stations'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  static public function addOneStation(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $stationField = $form_state->get('stations_' . $button['#parents'][2]);
    $addButton = $stationField + 1;
    $form_state->set('stations_' . $button['#parents'][2], $addButton);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  static function removeCallbackStation(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $stationField = $form_state->get('stations_' . $button['#parents'][2]);
    if ($stationField > 0) {
      $removeButton = $stationField - 1;
      $form_state->set('stations_' . $button['#parents'][2], $removeButton);
    }
    $form_state->setRebuild();
  }

}