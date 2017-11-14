<?php

namespace Drupal\train_booking\Form\Admin;

use Drupal\master\Form\Admin\ConfigForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

class TrainBookingConfigForm extends ConfigForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'train_booking_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->configFactory->get('train_booking.settings');

    // Popular stations.
    $popular_stations = $config->get('popular_stations');
    $name_field = $form_state->get('num_stations');
    $form['#tree'] = TRUE;
    $form['stations_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Popular Stations'),
      '#prefix' => '<div id="stations-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    if (!isset($name_field)) {
      $name_field = count($popular_stations);
      $form_state->set('num_stations', $name_field);
    }
    for ($i = 0; $i < $name_field; $i++) {
      $station = $this->entityTypeManager->getStorage('station')->load($popular_stations[$i]);
      $form['stations_fieldset']['station'][$i] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'station',
        '#default_value' => $station,
      ];
    }
    $form['stations_fieldset']['actions']['add_station'] = [
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#submit' => array('::addOne'),
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'stations-fieldset-wrapper',
      ],
    ];
    if ($name_field > 0) {
      $form['stations_fieldset']['actions']['remove_stations'] = [
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#submit' => array('::removeCallback'),
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'stations-fieldset-wrapper',
        ],
      ];
    }
    $form_state->setCached(FALSE);

    $multileg_buffer_time_between_trains = $config->get('multileg_buffer_time_between_trains');
    $user_popular_stations_limit = $config->get('user_popular_stations_limit');
    $form['multileg_buffer_time_between_trains_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Multileg buffer time between trains'),
    ];
    $form['multileg_buffer_time_between_trains_fieldset']['multileg_buffer_time_between_trains'] = [
      '#title' => $this->t('Number of minutes'),
      '#type' => 'number',
      '#default_value' => !empty($multileg_buffer_time_between_trains) ? $multileg_buffer_time_between_trains / 60 : 0,
      '#min' => 0,
    ];
    $form['user_popular_stations_limit_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User popular stations limit'),
    ];
    $form['user_popular_stations_limit_fieldset']['user_popular_stations_limit'] = [
      '#title' => $this->t('Number of stations'),
      '#type' => 'number',
      '#default_value' => !empty($user_popular_stations_limit) ? $user_popular_stations_limit : 0,
      '#min' => 0,
    ];
    $confidenceBlockData = $config->get('confidence_block');
    $form['confidence_block'] = [
      '#title' => $this->t('Book with confidence'),
      '#type' => 'text_format',
      '#format' => isset($confidenceBlockData['format']) ? $confidenceBlockData['format'] : 'full_html',
      '#default_value' => isset($confidenceBlockData['value']) ? $confidenceBlockData['value'] : '',
    ];
    $form['google_conversion_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google conversion id for timetable'),
      '#default_value' => $config->get('google_conversion_id'),
    ];
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
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return mixed
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['stations_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $station_field = $form_state->get('num_stations');
    $add_button = $station_field + 1;
    $form_state->set('num_stations', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $station_field = $form_state->get('num_stations');
    if ($station_field > 0) {
      $remove_button = $station_field - 1;
      $form_state->set('num_stations', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('train_booking.settings');
    $values = $form_state->getValues();

    // Popular stations.
    $popular_stations = [];
    if (isset($form['stations_fieldset']['station'])) {
      $popular_stations = $form_state->getValue($form['stations_fieldset']['station']['#parents']);
    }
    $config->set('popular_stations', $popular_stations);

    $multileg_buffer_time_between_trains = $form_state->getValue($form['multileg_buffer_time_between_trains_fieldset']['multileg_buffer_time_between_trains']['#parents']);
    $config->set('multileg_buffer_time_between_trains', $multileg_buffer_time_between_trains * 60);
    $user_popular_stations_limit = $form_state->getValue($form['user_popular_stations_limit_fieldset']['user_popular_stations_limit']['#parents']);
    $config->set('user_popular_stations_limit', $user_popular_stations_limit);

    // Confidence Block
    $config->set('confidence_block', $values['confidence_block']);

    $config->set('google_conversion_id', $values['google_conversion_id']);

    $config->save();
  }

}
