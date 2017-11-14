<?php

namespace Drupal\train_provider;

use Drupal\Core\Form\FormStateInterface;

trait AvailableRoutesFormTrait {

  /**
   * Get Available routes form for specific train provider.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function getAvailableRoutesSettingsForm(array &$form, FormStateInterface $form_state) {
    $availableRoutes = isset($this->configuration['available_routes']) ?
      $this->configuration['available_routes'] : [];
    $routesNumber = $form_state->get('num_routes');
    $form['routes_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Available routes'),
      '#prefix' => '<div id="routes-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#tree' => true,
    ];
    if (!isset($routesNumber)) {
      $routesNumber = count($availableRoutes);
      $form_state->set('num_routes', $routesNumber);
    }
    $form['routes_fieldset']['available_routes'] = [
      '#type' => 'container',
    ];
    for ($i = 0; $i < $routesNumber; $i++) {
      $departureStation = $arrivalStation = null;
      $form['routes_fieldset']['available_routes'][$i] = [
        '#type' => 'container',
        '#markup' => '#' . ($i + 1),
        '#attributes' => [
          'class' => [
            'container-inline',
          ]
        ]
      ];
      if (isset($availableRoutes[$i])) {
        $departureStation = $this->entityTypeManager->getStorage('station')->load($availableRoutes[$i]['departure_station']);
        $arrivalStation = $this->entityTypeManager->getStorage('station')->load($availableRoutes[$i]['arrival_station']);
      }
      $form['routes_fieldset']['available_routes'][$i]['departure_station'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'station',
        '#title' => $this->t('From'),
        '#default_value' => $departureStation,
      ];
      $form['routes_fieldset']['available_routes'][$i]['arrival_station'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'station',
        '#title' => $this->t('To'),
        '#default_value' => $arrivalStation,
      ];
    }
    $form['routes_fieldset']['actions']['add_route'] = [
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#submit' => [self::class . '::addOne'],
      '#ajax' => [
        'callback' => self::class . '::addmoreCallback',
        'wrapper' => 'routes-fieldset-wrapper',
      ],
    ];
    if ($routesNumber > 0) {
      $form['routes_fieldset']['actions']['remove_route'] = [
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#submit' => [self::class . '::removeCallback'],
        '#ajax' => [
          'callback' => self::class . '::addmoreCallback',
          'wrapper' => 'routes-fieldset-wrapper',
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
  static public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['routes_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  static public function addOne(array &$form, FormStateInterface $form_state) {
    $routeField = $form_state->get('num_routes');
    $addButton = $routeField + 1;
    $form_state->set('num_routes', $addButton);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  static function removeCallback(array &$form, FormStateInterface $form_state) {
    $routeField = $form_state->get('num_routes');
    if ($routeField > 0) {
      $removeButton = $routeField - 1;
      $form_state->set('num_routes', $removeButton);
    }
    $form_state->setRebuild();
  }

}