<?php

namespace Drupal\train_booking\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Url;
use Drupal\lead\Entity\Lead;
use Drupal\master\MasterMaxMind;
use Drupal\salesforce\SalesforceSync;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class SaveSearchForm extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\master\MasterMaxMind
   */
  protected $maxMind;

  /**
   * @var \Drupal\salesforce\SalesforceSync
   */
  protected $salesforceSync;

  /**
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\master\MasterMaxMind $max_mind
   * @param \Drupal\salesforce\SalesforceSync $salesforce_sync
   */
  public function __construct(EntityTypeManager $entity_type_manager, MasterMaxMind $max_mind, SalesforceSync $salesforce_sync, LanguageManager $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->maxMind = $max_mind;
    $this->salesforceSync = $salesforce_sync;
    $this->languageManager = $language_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'), $container->get('master.maxmind'), $container->get('salesforce_sync'), $container->get('language_manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'save_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $session_id = null) {
    $form = [
      '#attributes' => ['class' => ['save-search-form']]
    ];
    $form['title'] = [
      '#type' => 'container',
      '#markup' => $this->t('Save search', [], ['context' => 'Save search']),
      '#attributes' => ['class' => ['save-search-form-title']],
    ];
    $form['description'] = [
      '#type' => 'container',
      '#markup' => $this->t('Save this search now to easily come back later. We will send you a quick link and always keep you updated on availability for this date and route', [], ['context' => 'Save search']),
      '#attributes' => ['class' => ['save-search-form-text']],
    ];
    $form['error'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['error', 'container-wrapper']]
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#placeholder' => $this->t('Name', [], ['context' => 'Save search']),
      '#required' => true,
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#placeholder' => $this->t('Email', [], ['context' => 'Save search']),
      '#required' => true,
    ];
    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['actions-button-wrapper']],
    ];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#name' => 'save_search',
      '#value' => $this->t('Save', [], ['context' => 'SaveSearchForm']),
      '#submit' => [[$this, 'submitForm']],
      '#ajax' => [
        'callback' => [$this, 'saveSearchCallback'],
        'progress' => ['type' => 'none'],
      ],
      '#attributes' => ['class' => ['save-search-button']],
    ];

    return $form;
  }

  /**
   * Ajax callback.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function saveSearchCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Show errors for users in the popup.
    $selector = '.save-search-form .error.container-wrapper';
    $content = $this->getDrupalMessages();
    if (!empty($content)) {
      $response->addCommand(new ReplaceCommand($selector, $content));
    }

    // Show thank you form, if form was submitted successfully.
    if (!$form_state::hasAnyErrors()) {
      $selector = '.save-search-form';
      $response->addCommand(new ReplaceCommand($selector, $this->getThankYouMessage()));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = explode(' ', $form_state->getValue('name'), 2);
    $leadData['first_name'] = $name[0];
    $leadData['last_name'] = count($name) == 1 ? $name[0] : $name[1] ;
    $leadData['email'] = $form_state->getValue('email');

    $request = $this->getRequest();
    $data = $this->getDataFromUrl($request, $form_state);
    $searchRequest = $this->submitDataFromUrlToStore($data['form_mode'], $data['leg_data'], $data['passengers_data']);
    $parameters = $this->getParametersFromSearchRequest($searchRequest);

    // Create Url.
    $language = $this->languageManager->getCurrentLanguage();
    $url = Url::fromRoute('<front>', [], ['query' => $parameters, 'language' => $language]);
    $url->setAbsolute(true);

    // Create route string.
    /** @var \Drupal\train_base\Entity\Station $departureStation */
    $departureStation = $this->entityTypeManager->getStorage('station')->load($searchRequest['legs'][1]['departure_station']);
    /** @var \Drupal\train_base\Entity\Station $arrivalStation */
    $arrivalStation = $this->entityTypeManager->getStorage('station')->load($searchRequest['legs'][1]['arrival_station']);
    $route = $departureStation->getName() . ' - ' . $arrivalStation->getName();

    $lead = Lead::create($leadData)
      ->setData('departure_date', $searchRequest['legs'][1]['departure_date'])
      ->setData('search_url', $url->toString())
      ->setData('ip', $request->getClientIp())
      ->setData('country', $this->maxMind->getCountry())
      ->setData('city', $this->maxMind->getCity())
      ->setData('current_language_code', $language->getId())
      ->setData('route', $route)
      ->setData('lead_source', 'Site')
      ->setData('sign_up_source', 'Saved search');
    $lead->save();

    $this->salesforceSync->entityCrud($lead, SalesforceSync::OPERATION_UPDATE);
  }

  /**
   * Gets a renderable array of errors.
   *
   * @return array
   */
  protected function getDrupalMessages() {
    $output = [];
    $drupal_messages = drupal_get_messages('error');
    if (!empty($drupal_messages)) {
      $output = [
        '#prefix' => '<div class="error container-wrapper">',
        '#suffix' => '</div>',
        '#theme' => 'status_messages',
        // @todo Improve when https://www.drupal.org/node/2278383 lands.
        // Is available in Drupal 8.4
        '#message_list' => $drupal_messages,
        '#status_headings' => [
          'error' => t('Error message', [], ['context' => 'Save search']),
        ],
      ];
    }

    return $output;
  }

  /**
   * Gets a thank you message, which will be show after submitting.
   *
   * @return array
   */
  protected function getThankYouMessage() {
    $message = [
      '#type' => 'container',
      '#attributes' => ['class' => ['thank-you-wrapper']]
    ];
    $message['title'] = [
      '#type' => 'container',
      '#markup' => $this->t('Thank you', [], ['context' => 'Save search']),
      '#attributes' => ['class' => ['thank-you-title']],
    ];
    $message['description'] = [
      '#type' => 'container',
      '#markup' => $this->t('Your search has been saved and sent to your email address', [], ['context' => 'Save search']),
      '#attributes' => ['class' => ['thank-you-description']],
    ];

    return $message;
  }

  // @TODO was copypasted from SearchForm. In the future realize in the trait.
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
      $departureStation = $this->entityTypeManager->getStorage('station')->load($data['departure_station']);
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

  // @TODO was copypasted from SearchForm. In the future realize in the trait.
  /**
   * Converts parameters from request to array of parameters.
   *
   * @param Request $request
   * @param $form_state
   * @return array
   */
  protected function getDataFromUrl(Request $request, $form_state) {
    $leg_data = $passengers_data = $picked_stations = [];
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

    return ['form_mode' => $request->get('form-mode'), 'leg_data' => $leg_data, 'passengers_data' => $passengers_data, 'picked_stations' => $picked_stations];
  }

  // @TODO was copypasted from TrainBookingBaseForm. In the future realize in the trait.
  /**
   * @param $searchRequest
   * @return mixed
   */
  protected function getParametersFromSearchRequest($searchRequest) {
    foreach ($searchRequest['legs'] as $leg => $legData) {
      foreach ($legData as $label => $value) {
        if (!empty($value)) {
          if ($label == 'departure_date') {
            $value = $value->format('Y-m-d');
          }
          $parameters['legs'][$leg][$label] = $value;
        }
      }
    }
    $parameters['passengers']['adults'] = $searchRequest['adults'];
    if ($searchRequest['children'] > 0) {
      $parameters['passengers']['children'] = $searchRequest['children'];
      for ($i = 0; $i < $searchRequest['children']; $i++) {
        $parameters['passengers']['children_age']['child_' . $i] = $searchRequest['children_age'][$i];
      }
    }
    $formMode = 'basic-mode';
    if (isset($searchRequest['complex_trip']) && isset($searchRequest['round_trip'])) {
      if ($searchRequest['complex_trip'] === TRUE && $searchRequest['round_trip'] === FALSE) {
        $formMode = 'complex-mode';
      }
      else {
        if ($searchRequest['complex_trip'] === TRUE && $searchRequest['round_trip'] === TRUE) {
          $formMode = 'roundtrip-mode';
        }
      }
    }
    $parameters['form-mode'] = $formMode;

    return $parameters;
  }

}
