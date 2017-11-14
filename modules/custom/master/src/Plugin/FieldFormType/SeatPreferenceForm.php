<?php

namespace Drupal\master\Plugin\FieldFormType;

use Drupal\master\FieldFormTypeBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'SeaPreferenceForm'.
 *
 * @FieldFormType(
 *   id = "seat_preference_form",
 *   label = @Translation("Seat Preference Form"),
 * )
 */
class SeatPreferenceForm extends FieldFormTypeBase implements FieldFormTypeWithSummaryInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormElements($parameters = []) {
    $form = [];

    $seatPreferences = $this->getSeatPreference($parameters);
    $defaultValue = $parameters['default_value'];
    $form['description'] = [
      '#type' => 'container',
      '#markup' => t('We always assign our travelers to the best seats available onboard the train. Please choose your preferable seat location from the options below.', [], ['context' => 'SeatPreferenceForm']),
    ];
    if ($seatPreferences) {
      /** @var \Drupal\train_base\Entity\SeatPreference $seatPreference */
      foreach ($seatPreferences as $key => $seatPreference) {
        $form['seat_preference'][$key] = [
          '#type' => 'checkbox',
          '#title' => $seatPreference->getName(),
          '#default_value'=> (!empty($defaultValue[$key])) ? true : null,
        ];
      }
    }
    else {
      $form['hide_actions'] = [
        '#type' => 'value',
        '#value' => true,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    $result = [];
    $values = $form_state->getValue($form['#parents']);
    foreach ($values['seat_preference'] as $seatPreferenceId => $position) {
      if ($position) {
        /** @var \Drupal\train_base\Entity\SeatPreference $seatPreference */
        $seatPreference = \Drupal::service('entity_type.manager')->getStorage('seat_preference')->load($seatPreferenceId);
        $result[$seatPreferenceId] = $seatPreference->getName();
      }
    }

    return $result;
  }

  /**
   * @param $parameters
   * @return mixed
   */
  protected function getSeatPreference($parameters) {
    $supplier = !empty($parameters['supplier']) ? $parameters['supplier'] : [];
    $seatType = !empty($parameters['seat_type']) ? $parameters['seat_type'] : [];
    $carService = !empty($parameters['car_service']) ? $parameters['car_service'] : [];

    /** @var \Drupal\Core\Entity\EntityStorageInterface $seatPreferenceStorage */
    $seatPreferenceStorage = \Drupal::service('entity_type.manager')->getStorage('seat_preference');
    $query = $seatPreferenceStorage->getQuery();

    // Seat preference should be turn on.
    $query->condition('status', 1);

    // Supplier condition.
    if (!empty($supplier)) {
      $supplierCondition = $query->orConditionGroup()
        ->condition('supplier', $supplier, 'IN')
        ->notExists('supplier');
      $query->condition($supplierCondition);
    }
    else {
      $query->notExists('supplier');
    }

    // Seat type condition.
    if (!empty($seatType)) {
      $seatTypeCondition = $query->orConditionGroup()
        ->condition('seat_type', $seatType, 'IN')
        ->notExists('seat_type');
      $query->condition($seatTypeCondition);
    }
    else {
      $query->notExists('seat_type');
    }

    // Car service condition.
    if (!empty($carService)) {
      $carServiceCondition = $query->orConditionGroup()
        ->condition('car_service', $carService, 'IN')
        ->notExists('car_service');
      $query->condition($carServiceCondition);
    }
    else {
      $query->notExists('car_service');
    }

    $seatPreferences =  $query->execute();
    if ($seatPreferences) {
      $seatPreferences = $seatPreferenceStorage->loadMultiple($seatPreferences);
    }

    return $seatPreferences;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary($submittedData) {
    return implode('; ', $submittedData);
  }
}
