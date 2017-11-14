<?php

namespace Drupal\train_booking\Form;

use Drupal\booking\BookingManagerBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\train_booking\TrainBookingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;

/**
 * Class PassengerForm.
 *
 * @package Drupal\train_booking\Form
 */
class PassengerForm extends TrainBookingBaseForm  {

  /**
   * @var \Drupal\master\FieldFormTypeManager
   */
  protected $fieldFormTypeManager;

  /**
   * @var \Drupal\store\Entity\StoreOrder
   */
  protected $order;

  /**
   * @var \Drupal\master\FieldFormTypeBase[]
   */
  protected $plugins;

  /**
   * Return true if for round trip use same provider.
   *
   * @var boolean
   */
  protected $isProviderMonopolist;

  /**
   * PassengerForm constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  public function __construct(ContainerInterface $container) {
    parent::__construct($container);
    $this->fieldFormTypeManager = $container->get('plugin.manager.field_form_type');
    $this->isProviderMonopolist = false;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'train_booking_passenger_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $session_id = NULL) {
    $this->store->setSessionId($session_id);
    try {
      $this->store->getSessionExpirationTime();
    }
    catch (\Exception $e) {
      if ($link = $this->getSearchLink()) {
        drupal_set_message($this->t('Session has been expired. @link', ['@link' => $link], ['context' => 'Session expired']), 'warning');
      }
      else {
        drupal_set_message($this->t('Session has been expired.', [], ['context' => 'Session expired']), 'warning');
      }
      return new RedirectResponse($this->getRedirectUrl());
    }

    $this->updateSuccessSearchStat();
    $this->bookingManager->setStore($this->store);

    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'train_booking/passenger-form';
    $form['#attached']['library'][] = 'train_booking/open-ticket';
    $form['#attached']['library'][] = 'train_booking/scroll-to-ticket';
    $form['#attached']['library'][] = 'train_booking/save-passenger-details';
    $form['#cache'] = ['max-age' => 0];

    $form['main'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'route-legs-wrapper',
      ],
    ];
    $form['main']['legs'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $optionalServices = $deliveryServices = [];
    $timetableResult = $this->store->get(TrainBookingManager::TIMETABLE_RESULT_KEY);
    if (isset($timetableResult)) {
      if ($this->isRoundTrip()) {
        if ($timetableResult[1]['coach_class_info']->getPluginId() != 'local_train_provider'
          && $timetableResult[1]['coach_class_info']->getPluginId() != 'express3_train_provider'
          && $timetableResult[1]['coach_class_info']->getPluginId() == $timetableResult[2]['coach_class_info']->getPluginId()) {
          $this->isProviderMonopolist = true;
        }
      }
      // Initialize order and order items.
      $this->bookingManager->getOrder();
      $userCurrency = $this->defaultCurrency->getUserCurrency();
      if ($this->store->get(BookingManagerBase::USER_CURRENCY_KEY) != $userCurrency) {
        $this->store->set(BookingManagerBase::USER_CURRENCY_KEY, $userCurrency);
        $this->updateTimetableResult();
        $this->bookingManager->updateOrderItems();
      }
      foreach ($timetableResult as $leg => $result) {
        // If no result display message and redirect to timetable for existing search request or to home in other case.
        if (empty($result)) {
          if ($link = $this->getSearchLink()) {
            drupal_set_message($this->t('Session has been expired. @link', ['@link' => $link], ['context' => 'Session expired']), 'warning');
          }
          else {
            drupal_set_message($this->t('Session has been expired.', [], ['context' => 'Session expired']), 'warning');
          }
          return new RedirectResponse($this->getRedirectUrl());
        }
        $passengerFormId = $result['train_info']->getSupplier()->getPassengerFormType();
        $form['main']['legs'][$leg]['route_leg_info'] = $this->generateRouteLegInfo($leg);

        // Round trip from external providers expects same passengers data for each leg.
        if (!($this->isProviderMonopolist && $leg == 1)) {
          $form['main']['legs'][$leg]['all_passengers'] = $this->getPassengerForm($leg, $result);
        }
        $form['main']['legs'][$leg]['passengers_form_type'] = [
          '#type' => 'value',
          '#value' => $passengerFormId,
        ];

        $additionalProducts = $this->getAdditionalProducts($result);
        if (isset($additionalProducts['optional_service_ids'])) {
          $optionalServices += $additionalProducts['optional_service_ids'];
        }
        if (isset($additionalProducts['delivery_service_ids'])) {
          $deliveryServices += $additionalProducts['delivery_service_ids'];
        }
      }

      // Add optional and delivery services to the form.
      $form['main']['services'] = $this->getServicesForm($timetableResult, $optionalServices, $deliveryServices);

      $form['main']['notes'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Notes', [], ['context' => 'Passenger Form']),
        '#title_display' => 'none',
        '#attributes' => [
          'placeholder' => $this->t('Notes', [], ['context' => 'Passenger Form']),
        ],
      ];
      if ($this->currentUser->isAnonymous()) {
        $form['main']['email'] = [
          '#type' => 'email',
          '#title' => FALSE,
          '#required' => TRUE,
          '#attributes' => [
            'placeholder' => $this->t('E-mail', [], ['context' => 'Passenger Form']),
          ]
        ];
      }
      else {
        $form['main']['email'] = [
          '#type' => 'value',
          '#value' => $this->currentUser->getEmail(),
        ];
      }

      // Add sidebar to the form.
      $form['sidebar'] = $this->generateSidebarInfo();

    }
    else {
      if ($link = $this->getSearchLink()) {
        drupal_set_message($this->t('Session has been expired. @link', ['@link' => $link], ['context' => 'Session expired']), 'warning');
      }
      else {
        drupal_set_message($this->t('Session has been expired.', [], ['context' => 'Session expired']), 'warning');
      }
      return new RedirectResponse($this->getRedirectUrl());
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'main_submit',
      '#value' => $this->t('Continue to payment', [], ['context' => 'Passenger Form']),
      '#button_type' => 'primary',
      '#weight' => 100,
      '#attributes' => [
        'class' => [
          'to-payment'
        ]
      ]
    ];

    $form['#pre_render'] = array(
      array($this, 'addBottomBlock')
    );

    $this->trainBookingLogger->logPassengerForm($this->store->getSessionId(), $this->bookingManager->getOrderItems(), $this->bookingManager->getOrder());
    $this->trainBookingLogger->logLastStep($this->store->getSessionId(), 3);

    return $form;
  }

  public function addBottomBlock(array $form) {
    $form['main']['bottom_block'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'bottom-block',
        ],
      ],
      '#weight' => 0,
    ];

    $form['main']['bottom_block']['title'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'bottom-block-title',
        ],
      ],
      '#weight' => 0,
      '#markup' => $this->t('Client details', [], ['context' => 'Passenger
      Form']),
    ];

    $form['main']['bottom_block']['fields'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'bottom-block-fields',
        ],
      ],
    ];

    $form['main']['bottom_block']['fields']['email'] = $form['main']['email'];
    $form['main']['bottom_block']['fields']['email']['#weight'] = 0;
    unset($form['main']['email']);
    $form['main']['bottom_block']['fields']['notes'] = $form['main']['notes'];
    unset($form['main']['notes']);
    $form['main']['bottom_block']['fields']['notes']['#weight'] = 1;

    $form['sidebar']['#weight'] = 1;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Need be sure this submit consists passenger data (disabled functionality with provide details later).
    if (!empty($values['legs'])/* && $values['legs']['1']['all_passengers']['passengers_header']['provide_later_wrapper']['provide_later'] != 1*/) {
      // @todo Reorganize it. Look at ::SubmitForm.
      $button = $form_state->getTriggeringElement();
      if ($button['#name'] == 'main_submit') {
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
          $form_state->setError($form['main']['email'], t('Email format is incorrect', ['@title' => $form['main']['email']['#title']], ['context' => 'Passenger Form']));
        }
        $this->validateSubmitHelper($form, $form_state, 'validate');
      }
      else {
        if (!empty($button['#parents'][5]) && $button['#parents'][5] == 'save_details') {
          $leg = $button['#parents'][1];
          $passenger = $button['#parents'][3];
          $pluginId = $form['main']['legs'][$leg]['passengers_form_type']['#value'];
          $passengerForm = $form['main']['legs'][$leg]['all_passengers'][$passenger]['fields'];
          $plugin = $this->getFieldFormTypePlugin($pluginId);
          $plugin->validateForm($passengerForm[$pluginId], $form_state);
        }
        else {
          if (end($button['#parents']) != 'delete') {
            $serviceType = $button['#parents'][1];
            $key = $button['#parents'][2];
            $pluginId = $form['main']['services'][$serviceType][$key]['form']['plugin_id']['#value'];
            $pluginForm = $form['main']['services'][$serviceType][$key]['form']['plugin_form'];
            $plugin = $this->getFieldFormTypePlugin($pluginId);
            $plugin->validateForm($pluginForm, $form_state);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Need to be sure this submit consists passenger data (disabled functionality with provide details later).
    $button = $form_state->getTriggeringElement();
    if (!empty($values['legs'])/* && $values['legs']['1']['all_passengers']['passengers_header']['provide_later_wrapper']['provide_later'] != 1*/) {
      // @todo Reorganize it. Look at ::ValidateForm.
      if ($button['#name'] == 'main_submit') {
        // @todo Each submit of passenger page form must be validated, passenger data stored in the session store.
        // It has to check passengers age, update ticket order items and render right sidebar
        $this->validateSubmitHelper($form, $form_state, 'submit');
        $this->store->set(TrainBookingManager::EMAIL_KEY, $form_state->getValue('email'));
        $this->store->set(TrainBookingManager::NOTES_KEY, $form_state->getValue('notes'));
        $url = Url::fromRoute('train_booking.payment_form', ['session_id' => $this->store->getSessionId()]);
        $this->trainBookingLogger->logPassengerForm($this->store->getSessionId(), $this->bookingManager->getOrderItems(), $this->bookingManager->getOrder());
        $form_state->setRedirectUrl($url);
      }
      else {
        if (!empty($button['#parents'][5]) && $button['#parents'][5] == 'save_details') {
          $leg = $button['#parents'][1];
          $passenger = $button['#parents'][3];
          $pluginId = $form['main']['legs'][$leg]['passengers_form_type']['#value'];
          $passengerForm = $form['main']['legs'][$leg]['all_passengers'][$passenger]['fields'];
          $plugin = $this->getFieldFormTypePlugin($pluginId);
          $form_state->setLimitValidationErrors($passengerForm[$pluginId]);
          $submittedData = $plugin->submitForm($passengerForm[$pluginId], $form_state);
          // Forced updating of second leg order items if use_details_for_roundtrip is checked.
          if (isset($passengerForm['use_details_for_roundtrip'])
            && $form_state->getValue($passengerForm['use_details_for_roundtrip']['#parents']) == 1) {
            $secondPassengerForm = &$form['main']['legs']['2']['all_passengers'][$passenger];
            $this->updateRelatedPassengerForm($secondPassengerForm['fields'][$pluginId], $submittedData);
            $secondPassengerForm['form_header']['markup']['#markup'] = $this->getPassengerHeaderInfo($submittedData);
            if ($pluginId == TrainBookingBaseForm::RU_PASSENGER_FORM && !empty($submittedData['dob'])) {
              $this->postSubmitFormUpdating($submittedData['dob'], '2', $passenger);
            }
          }
          // Forced updating of first leg order items if provider is monopolist.
          if ($this->isProviderMonopolist && $leg == 2) {
            if ($pluginId == TrainBookingBaseForm::RU_PASSENGER_FORM && !empty($submittedData['dob'])) {
              $this->postSubmitFormUpdating($submittedData['dob'], '1', $passenger);
            }
          }

          // Cause only RU form has age, so need updating timetable store data (pax) and sidebar info only fo RU form.
          if ($pluginId == TrainBookingBaseForm::RU_PASSENGER_FORM && !empty($submittedData['dob'])) {
            $this->postSubmitFormUpdating($submittedData['dob'], $leg, $passenger);
          }
          $form['main']['legs'][$leg]['all_passengers'][$passenger]['form_header']['markup']['#markup'] = $this->getPassengerHeaderInfo($submittedData);
          $form['sidebar'] = $this->generateSidebarInfo();
        }
      }
    }
    elseif (!empty($values['services'])) {
      $submittedData = [];
      $summary = '';
      $action = end($button['#parents']);
      $serviceType = $button['#parents'][1];
      $key = $button['#parents'][2];
      $serviceForm = $form['main']['services'][$serviceType][$key]['form'];
      $productId = $serviceForm['product_id']['#value'];
      $pluginId = $serviceForm['plugin_id']['#value'];
      $pluginForm = $serviceForm['plugin_form'];
      $plugin = $this->getFieldFormTypePlugin($pluginId);
      $form_state->setLimitValidationErrors($pluginForm);
      if ($action != 'delete') {
        $submittedData = $plugin->submitForm($pluginForm, $form_state);
        $summary = $plugin->getSummary($submittedData);
      }
      $this->updateServiceHeader($form['main']['services'][$serviceType][$key], $productId, $submittedData, $summary);
      $this->orderServicesHandler($serviceForm['product_id']['#value'], $submittedData);
      $this->bookingManager->updateOrderItems();
      $form['sidebar'] = $this->generateSidebarInfo();
    }
    else {
      $this->store->set(TrainBookingManager::EMAIL_KEY, $form_state->getValue('email'));
      $this->store->set(TrainBookingManager::NOTES_KEY, $form_state->getValue('notes'));
      $url = Url::fromRoute('train_booking.payment_form', ['session_id' => $this->store->getSessionId()]);
      $form_state->setRedirectUrl($url);
    }
  }

  /**
   * Ajax callback.
   * Replacing some parts of Passenger Form.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function saveDetailsCallback($form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $leg = $button['#parents'][1];
    $passenger = $button['#parents'][3];
    $passengerFormId = $form['main']['legs'][$leg]['passengers_form_type']['#value'];
    $response = new AjaxResponse();
    $selector = '#highlighted .container-wrapper';
    $content = $this->getDrupalMessages();
    if (!empty($content)) {
      $response->addCommand(new ReplaceCommand($selector, $content));
    }
    $selector = '#edit-sidebar';
    $content = $form['sidebar'];
    $response->addCommand(new ReplaceCommand($selector, $content));
    $passengerForm = $form['main']['legs'][$leg]['all_passengers'][$passenger];
    $form_header = $passengerForm['form_header'];
    $selector = '#' . $form_header['#attributes']['data-drupal-selector'] . ' .passenger-info';
    $content = $form_header['markup'];
    $response->addCommand(new ReplaceCommand($selector, $content));
    if (isset($passengerForm['fields']['use_details_for_roundtrip'])
      && $form_state->getValue($passengerForm['fields']['use_details_for_roundtrip']['#parents']) == 1) {
      $relatedPassengerForm = $form['main']['legs']['2']['all_passengers'][$passenger];
      $selector = '#' . $relatedPassengerForm['fields'][$passengerFormId]['#attributes']['data-drupal-selector'];
      $content = $relatedPassengerForm['fields'][$passengerFormId];
      $response->addCommand(new ReplaceCommand($selector, $content));
      $related_form_header = $relatedPassengerForm['form_header'];
      $selector = '#' . $related_form_header['#attributes']['data-drupal-selector'] . ' .passenger-info';
      $content = $related_form_header['markup'];
      $response->addCommand(new ReplaceCommand($selector, $content));
    }
    $form_state->setRebuild(true);

    return $response;
  }

  /**
   * Ajax callback.
   * Update services.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function updateServicesCallback($form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $serviceType = $button['#parents'][1];
    $key = $button['#parents'][2];
    $response = new AjaxResponse();
    $selector = '#highlighted .container-wrapper';
    $content = $this->getDrupalMessages();
    if (!empty($content)) {
      $response->addCommand(new ReplaceCommand($selector, $content));
    }
    $selector = '#edit-sidebar';
    $content = $form['sidebar'];
    $response->addCommand(new ReplaceCommand($selector, $content));
    $serviceHeader = $form['main']['services'][$serviceType][$key]['title'];
    $selector = '#' . $serviceHeader['#attributes']['data-drupal-selector'] . ' .service-title';
    $response->addCommand(new ReplaceCommand($selector, $serviceHeader['markup']));

    return $response;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param string $op
   */
  protected function validateSubmitHelper(array $form, FormStateInterface $form_state, $op) {
    $values = $form_state->getValues();

    // Validating/Submitting passenger data.
    $passengersInfo = [];
    foreach ($values['legs'] as $leg => $legData) {
      if ($this->isProviderMonopolist && $leg == 1) {
        continue;
      }
      $pluginId = $legData['passengers_form_type'];
      $plugin = $this->getFieldFormTypePlugin($pluginId);
      foreach ($legData['all_passengers'] as $passenger => $passengerData) {
        $pluginForm = $form['main']['legs'][$leg]['all_passengers'][$passenger]['fields'][$pluginId];

        // If suppliers are same, so we can use passenger data from first leg for second leg.
        $useDetailsForRoundtrip = false;
        if ($leg == '2' && !$this->isProviderMonopolist) {
          $passengerDataFromFirstLeg = $values['legs'][1]['all_passengers'][$passenger];
          if (isset($passengerDataFromFirstLeg['fields']['use_details_for_roundtrip'])
            && $passengerDataFromFirstLeg['fields']['use_details_for_roundtrip'] == 1
          ) {
            $useDetailsForRoundtrip = TRUE;
          }
        }

        switch ($op) {
          case 'validate':
            // We doesn't need validate passenger data for second leg, if use_details_for_roundtrip was checked.
            if (!$useDetailsForRoundtrip) {
              $plugin->validateForm($pluginForm, $form_state);
            }
            break;
          case 'submit':
            // If suppliers are same, so we can use passenger data from first leg for second leg.
            $submittedData = $useDetailsForRoundtrip ? $passengersInfo[1][$passenger] :
              $plugin->submitForm($pluginForm, $form_state);
            $passengersInfo[$leg][$passenger] = $submittedData;

            // Passenger data for roundtrip type and monopolist supplier must be same.
            if ($this->isProviderMonopolist) {
              $passengersInfo[1][$passenger] = $submittedData;
            }

            // Cause only RU form has age, so need updating timetable store data (pax) and sidebar info only fo RU form.
            if ($pluginId == TrainBookingBaseForm::RU_PASSENGER_FORM && !empty($submittedData['dob'])) {
              $this->postSubmitFormUpdating($submittedData['dob'], $leg, $passenger);
            }
            break;
        }
      }
    }
    // Save passenger data to the store.
    if (!empty($passengersInfo)) {
      ksort($passengersInfo);
      $this->store->set(TrainBookingManager::PASSENGERS_RESULT_KEY, $passengersInfo);
    }

    // Validating/Submitting services data.
    if (!empty($values['services'])) {
      foreach ($values['services'] as $currentServiceType => $services) {
        foreach ($services as $key => $service) {
          $serviceForm = $form['main']['services'][$currentServiceType][$key]['form'];
          $pluginId = $serviceForm['plugin_id']['#value'];
          $pluginForm = $serviceForm['plugin_form'];
          $plugin = $this->getFieldFormTypePlugin($pluginId);
          switch ($op) {
            case 'validate':
              $plugin->validateForm($pluginForm, $form_state);
              break;
            case 'submit':
              $submittedData = $plugin->submitForm($pluginForm, $form_state);
              $this->orderServicesHandler($serviceForm['product_id']['#value'], $submittedData);
              $this->bookingManager->updateOrderItems();
              break;
          }
        }
      }
    }

  }

  /**
   * Updates timetable result PAX and OrderItems.
   * Needed for clear displaying sidebar information.
   *
   * @param $dob
   * @param $leg
   * @param $passenger
   */
  protected function postSubmitFormUpdating($dob, $leg, $passenger) {
    $age = $this->doCalculateAge($dob);
    $timetableResult = $this->store->get(TrainBookingManager::TIMETABLE_RESULT_KEY);
    $timetableResult[$leg]['pax'][$passenger] = $age;
    $this->store->set(TrainBookingManager::TIMETABLE_RESULT_KEY, $timetableResult);
    $this->bookingManager->updateOrderItems();
  }

  /**
   * Returns part of form, which will be replace via Ajax.
   *
   * @param $form
   * @param $submittedData
   */
  protected function updateRelatedPassengerForm(&$form, $submittedData) {
    foreach ($submittedData as $field => $value) {
      if ($field == 'dob') {
        $form[$field]['dates']['year']['#options'] = [$value->format('Y') => $value->format('Y')];
        $form[$field]['dates']['month']['#options'] = [$value->format('n')=> $value->format('n')];
        $form[$field]['dates']['day']['#options'] = [$value->format('j') => $value->format('j')];
      }
      elseif ($field == 'gender' || $field == 'title' || $field == 'citizenship') {
        $form[$field]['#options'] = [$value => $value];
      }
      $form[$field]['#value'] = $value;
    }
  }

  /**
   * Gets the payment method plugin instance.
   *
   * @param string $pluginId
   * @return \Drupal\master\FieldFormTypeBase
   */
  protected function getFieldFormTypePlugin($pluginId) {
    if (!isset($this->plugins[$pluginId])) {
      $this->plugins[$pluginId] = $this->fieldFormTypeManager->createInstance($pluginId);
    }

    return $this->plugins[$pluginId];
  }

  /**
   * Gets all passengers form on each route.
   *
   * @param $leg
   * @param $result
   * @return array
   */
  protected function getPassengerForm($leg, $result) {
    /** @var \Drupal\train_base\Entity\Supplier $supplier */
    $supplier = $result['train_info']->getSupplier();
    $passenger_form_id = $supplier->getPassengerFormType();
    /** @var \Drupal\master\FieldFormTypeBase $plugin */
    $plugin = $this->getFieldFormTypePlugin($passenger_form_id);
    $form = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'all-passengers-wrapper',
          'leg-' . $leg,
        ],
      ],
    ];
    $form['#attributes']['class'][] = $plugin->isComplexForm() ? 'complex-form' : 'simple-form';
    $form['passengers_header']['passenger_details_header'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'passenger-details-header',
          'header'
        ],
      ],
    ];
    $form['passengers_header']['passenger_details_header']['label'] = [
      '#type' => 'label',
      '#title' => $this->t('Passenger details', [], ['context' => 'Passenger Form'])
    ];
    // @todo provider later
    /*if ($leg == '1') {
      $form['passengers_header']['provide_later_wrapper'] = $this->getProvideLater();
    }*/
    $form['passengers_header']['passenger_details_header']['message_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'provide-later-message',
      ],
    ];
    $form['passengers_header']['passenger_details_header']['message_wrapper']['#markup'] =
      $this->t('If it is less than 44 days prior to your departure we will ask you to fill out the
       travel card within the following 48 hours. Ticket availability and price are subject to change.', [], ['context' => 'Passenger Form']);

    /** @var \Drupal\train_base\Entity\Supplier $supplier */
    $supplier = $result['train_info']->getSupplier();
    $passengerFormId = $supplier->getPassengerFormType();
    $plugin = $this->getFieldFormTypePlugin($passengerFormId);
    $passengerForm = $plugin->getFormElements();

    $counter = 0;
    foreach ($result['pax'] as $passenger => $age) {
      $counter++;
      $passengerType = !$supplier->isInfant($age) ? $supplier->isChild($age)
        ? $this->t('Child', [], ['context' => 'Passenger category'])
        : $this->t('Adult', [], ['context' => 'Passenger category'])
        : $this->t('Infant', [], ['context' => 'Passenger category']);
      $form[$passenger] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'passenger-wrapper',
            $passenger,
            $this->getHtmlClass($passengerFormId)
          ],
        ],
      ];

      if(($counter == 1 && $leg == '1')
        || ($this->isProviderMonopolist &&  $counter == 1 && $leg == '2')) {
        $form[$passenger]['#attributes']['class'][] = 'opened';
      }

      $form[$passenger]['form_header'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'passenger-form-header-wrapper',
            $this->getHtmlClass($passengerType),
          ],
        ],
      ];
      $form[$passenger]['form_header']['markup'] = [
        '#markup' => $this->getPassengerHeaderPlaceholder($counter, $passengerType),
      ];
      $form[$passenger]['fields']  = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'fields-wrapper',
        ],
      ];
      $form[$passenger]['fields'][$passengerFormId] = $passengerForm;
      if ($this->isComplexTrip() && $this->isSameSuppliersPassengerForm() && $leg == '1') {
        $title = $this->t('Use passenger details for a complex trip ticket', [], ['context' => 'Passenger Form']);
        if ($this->isRoundTrip()) {
          $title = $this->t('Use passenger details for a round trip ticket', [], ['context' => 'Passenger Form']);
        }
        $form[$passenger]['fields']['use_details_for_roundtrip'] = [
          '#type' => 'checkbox',
          '#title' => $title,
          '#attributes' => [
            'class' => [
              'use-details',
            ],
          ],
        ];
      }
      if ($plugin->isComplexForm()) {
        $form[$passenger]['fields']['save_details'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'save-details-wrapper',
            ],
          ],
        ];
        $form[$passenger]['fields']['save_details']['button'] = [
          '#type' => 'submit',
          '#name' => 'save_details_' . $leg . '_' . $passenger,
          '#value' => t('Save details', [], ['context' => 'Passenger Form']),
          '#limit_validation_errors' => [
            ['legs']
          ],
          '#submit' => [[$this, 'submitForm']],
          '#ajax' => [
            'callback' => [$this, 'saveDetailsCallback'],
          ],
        ];
      }
    }

    return $form;
  }

  /**
   * Generates header placeholder for each passenger form.
   *
   * @param int $passenger
   * @param string $passengerType
   * @return string
   */
  protected function getPassengerHeaderPlaceholder(int $passenger, string $passengerType) {
    $output = '<span class="passenger-info">' . $this->t('Passenger #@passenger_key - @passenger_type',
        ['@passenger_key' => $passenger, '@passenger_type' => $passengerType], ['context' => 'Passenger Form']) . '</span>';

    return $output;
  }

  /**
   * Generates header info for each passenger form.
   *
   * @param array $passengerData
   * @return string
   */
  protected function getPassengerHeaderInfo(array $passengerData) {
    $output = '<span class="passenger-info accepted">';
    if (!empty($passengerData['title'])) {
      $output .= '<span class="title">' . $this->getPassengerTitle($passengerData['title']) . '</span>';
    }
    $output .= '<span class="name">' . $passengerData['first_name'] . ' ' . $passengerData['last_name'] . '</span>';
    if (!empty($passengerData['dob'])) {
      $output .= '<span class="dob">' . $passengerData['dob']->format('d.m.Y') . '</span>';
    }
    if (!empty($passengerData['id_number'])) {
      $output .= '<span class="id-number">' . $passengerData['id_number']. '</span>';
    }
    $output .= '</span>';

    return $output;
  }

  /**
   * Gets checkbox "Provide passenger details later".
   *
   */
  protected function getProvideLater() {
    $form = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'provide-later-wrapper',
        ],
      ],
    ];
    $form['provide_later'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I will provide passenger details later', [], ['context' => 'Passenger Form']),
    ];

    // @TODO: uncomment when client area is completed

    /*
    $form['booked_before'] = [
      '#type' => 'container',
      '#markup' => $this->t('Booked before? Sign in to select passengers'),
    ];
    $form['sign_in'] = [
      '#type' => 'container',
      '#markup' => $this->t('Sign in'),
      '#attributes' => [
        'class' => [
          'sign-in',
        ],
      ],
    ];
    */
    return $form;
  }

  /**
   * @param array $timetableResult
   * @param \Drupal\store\Entity\BaseProduct[] $optionalServices
   * @param \Drupal\store\Entity\BaseProduct[] $deliveryServices
   * @return array
   */
  protected function getServicesForm($timetableResult, $optionalServices, $deliveryServices) {
    $form = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => [
        'class' => [
          'services-wrapper',
        ],
      ],
    ];

    // Each service can have inherit logic, so we must prepare some parameters for them.
    // Such as suppliers, seat types and car services.
    $supplier = $seatType = $carServices = $carServiceIds = [];
    foreach ($timetableResult as $result) {
      /** @var \Drupal\train_provider\TrainInfoHolder $trainInfoHolder */
      $trainInfoHolder = $result['train_info'];
      /** @var \Drupal\train_provider\CoachClassInfoHolder $coachClassInfoHolder */
      $coachClassInfoHolder = $result['coach_class_info'];
      $supplier[] = $trainInfoHolder->getSupplier()->id();
      $seatType[] = $coachClassInfoHolder->getSeatType()->id();
      $carServices = array_merge($carServices, $coachClassInfoHolder->getCarServices());
    }
    $supplier = array_unique($supplier);
    $seatType = array_unique($seatType);
    if ($carServices) {
      foreach ($carServices as $carService) {
        $carServiceIds[] = $carService->id();
      }
    }
    $carServices = array_unique($carServiceIds);

    // Prepare picked data for clear displaying services.
    $allOrderItems = $this->bookingManager->getOrderItems();
    foreach ($allOrderItems as $orderItemType => $orderItems) {
      if ($orderItemType == 'optional_service' || $orderItemType == 'delivery_service') {
        foreach ($orderItems as $orderItem) {
          $productId = $orderItem->getProduct()->id();
          $pickedData[$productId] = $orderItem->getData('pickedData');
        }
      }
    }

    // Optional services.
    // We want to divide optional services on free services and paid services.
    $freeServicesExist = $paidServicesExist = false;
    foreach ($optionalServices as $optionalService) {
      // Sort optional services products to the free and paid.
      if ($optionalService->getPrice()->getNumber() == 0) {
        $freeServicesExist = true;
      }
      elseif ($optionalService->getPrice()->getNumber() > 0) {
        $paidServicesExist = true;
      }
    }
    if ($freeServicesExist) {
      $form['free_services'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'free-services-wrapper',
          ],
        ],
      ];
      $form['free_services']['title'] = [
        '#type' => 'container',
        '#markup' => $this->t('Free services', [], ['context' => 'Passenger Form']),
        '#attributes' => [
          'class' => [
            'free-services-title',
          ],
        ],
      ];
    }
    if ($paidServicesExist) {
      $form['paid_services'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'paid-services-wrapper',
          ],
        ],
      ];
      $form['paid_services']['title'] = [
        '#type' => 'container',
        '#markup' => $this->t('Paid services', [], ['context' => 'Passenger Form']),
        '#attributes' => [
          'class' => [
            'paid-services-title',
          ],
        ],
      ];
    }

    // Getting optional services forms.
    foreach ($optionalServices as $key => $optionalService) {
      // Sort optional services products to the free and paid.
      $currentServiceArea = $optionalService->getPrice()->getNumber() == 0 ? 'free_services' : 'paid_services';
      $pluginId = $optionalService->getFieldForm();
      $plugin = $this->getFieldFormTypePlugin($pluginId);
      $defaultValue = isset($pickedData[$optionalService->id()]) ? $pickedData[$optionalService->id()] : [];
      $pluginForm = $plugin->getFormElements(['supplier' => $supplier, 'seat_type' => $seatType,
        'car_service' => $carServices, 'default_value' => $defaultValue, 'description' => $optionalService->getDescription()]);
      $summary = $plugin->getSummary($defaultValue);
      $form[$currentServiceArea][$key] = $this->getServiceForm($optionalService, $key, $pluginId, $pluginForm, $defaultValue, $summary);
    }

    // Delivery services.
    if (!empty($deliveryServices)) {
      $form['delivery_services'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'delivery-services-wrapper',
          ],
        ],
      ];
      $form['delivery_services']['title'] = [
        '#type' => 'container',
        '#markup' => $this->t('Ticket delivery', [], ['context' => 'Passenger Form']),
        '#attributes' => [
          'class' => [
            'delivery-services-title',
          ],
        ],
      ];
    }

    // Getting delivery services forms.
    foreach ($deliveryServices as $key => $deliveryService) {
      $pluginId = $deliveryService->getFieldForm();
      $plugin = $this->getFieldFormTypePlugin($pluginId);
      $defaultValue = isset($pickedData[$deliveryService->id()]) ? $pickedData[$deliveryService->id()] : [];
      $pluginForm = $plugin->getFormElements(['supplier' => $supplier, 'seat_type' => $seatType,
        'car_service' => $carServices, 'default_value' => $defaultValue, 'description' => $deliveryService->getDescription()]);
      $summary = $plugin->getSummary($defaultValue);
      $form['delivery_services'][$key] = $this->getServiceForm($deliveryService, $key, $pluginId, $pluginForm, $defaultValue, $summary);
    }

    return $form;
  }

  /**
   * @param \Drupal\store\Entity\BaseProduct $service
   * @param $key
   * @param $pluginId
   * @param $pluginForm
   * @param $defaultValue
   * @param $summary
   * @return array
   */
  protected function getServiceForm($service, $key, $pluginId, $pluginForm, $defaultValue, $summary) {
    $form = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'service-wrapper',
        ],
      ],
    ];
    $form['title'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'service-header',
        ],
      ],
    ];
    $form['title']['markup'] = [
      '#markup' => $this->getServiceHeader($service, $defaultValue, $summary),
    ];
    $form['form'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'service-form-wrapper',
        ],
      ],
    ];
    $form['form']['plugin_form'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'plugin-form-wrapper',
        ],
      ],
    ];
    $form['form']['plugin_form'] += $pluginForm;
    $form['form']['plugin_id'] = [
      '#value' => $pluginId,
    ];
    $form['form']['service_type'] = [
      '#value' => $service->bundle(),
    ];
    $form['form']['product_id'] = [
      '#value' => $service->id(),
    ];
    if (empty($pluginForm['hide_actions'])) {
      $form['form']['actions'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'actions-button-wrapper',
          ],
        ],
      ];
      $form['form']['actions']['delete'] = [
        '#type' => 'submit',
        '#name' => 'delete_' . $service->bundle() . '_' . $key,
        '#value' => $this->t('Delete', [], ['context' => 'Passenger Form']),
        '#limit_validation_errors' => [
          ['services']
        ],
        '#submit' => [[$this, 'submitForm']],
        '#ajax' => [
          'callback' => [$this, 'updateServicesCallback'],
          'progress' => ['type' => 'none'],
        ],
      ];
      $form['form']['actions']['save'] = [
        '#type' => 'submit',
        '#name' => 'save_' . $service->bundle() . '_' . $key,
        '#value' => $this->t('Save', [], ['context' => 'Passenger Form']),
        '#limit_validation_errors' => [
          ['services']
        ],
        '#submit' => [[$this, 'submitForm']],
        '#ajax' => [
          'callback' => [$this, 'updateServicesCallback'],
          'progress' => ['type' => 'none'],
        ],
      ];
    }

    return $form;
  }

  /**
   * Return array of optional services and delivery services.
   *
   * @param $data
   * @return array
   */
  protected function getAdditionalProducts($data) {
    $output = [];
    /** @var \Drupal\train_provider\TrainInfoHolder $trainInfoHolder */
    $trainInfoHolder = $data['train_info'];
    /** @var \Drupal\train_provider\CoachClassInfoHolder $coachClassInfoHolder */
    $coachClassInfoHolder = $data['coach_class_info'];
    $output['delivery_service_ids'] = [];
    $stations = [$trainInfoHolder->getDepartureStation()->id(), $trainInfoHolder->getArrivalStation()->id()];

    $query = $this->entityQuery->get('base_product');
    $query->condition('type', 'optional_service');
    $query->condition('status', 1);
    $query->condition('third_step', 1);

    //Train class condition.
    $trainClassCondition = $query->orConditionGroup()
      ->condition('train_class', $trainInfoHolder->getTrainClass()->id())
      ->notExists('train_class');
    $query->condition($trainClassCondition);

    //Coach class condition.
    $coachClassCondition = $query->orConditionGroup()
      ->condition('coach_class', $coachClassInfoHolder->getCoachClass()->id())
      ->notExists('coach_class');
    $query->condition($coachClassCondition);

    //Seat type condition.
    $seatTypeCondition = $query->orConditionGroup()
      ->condition('seat_type', $coachClassInfoHolder->getSeatType()->id())
      ->notExists('seat_type');
    $query->condition($seatTypeCondition);

    //Train station condition.
    $trainStationCondition = $query->orConditionGroup()
      ->condition('train_station', $stations, 'IN')
      ->notExists('train_station');
    $query->condition($trainStationCondition);

    //train condition.
    if ($trainInfoHolder->getTrain()) {
      $trainCondition = $query->orConditionGroup()
        ->condition('train', $trainInfoHolder->getTrain()->id())
        ->notExists('train');
      $query->condition($trainCondition);
    }

    $optionalServiceIds = $query->execute();
    if ($optionalServiceIds) {
      $output['optional_service_ids'] = $this->entityTypeManager->getStorage('base_product')->loadMultiple($optionalServiceIds);
    }

    if ($trainInfoHolder->isEticketAvailable()) {
      $query = $this->entityQuery->get('base_product');
      $query->condition('type', 'delivery_service');
      $query->condition('status', 1);

      //Train class condition.
      $trainClassCondition = $query->orConditionGroup()
        ->condition('train_class', $trainInfoHolder->getTrainClass()->id())
        ->notExists('train_class');
      $query->condition($trainClassCondition);

      //Train station condition.
      $trainStationCondition = $query->orConditionGroup()
        ->condition('train_station', $stations, 'IN')
        ->notExists('train_station');
      $query->condition($trainStationCondition);
      $deliveryServiceIds = $query->execute();

      if ($deliveryServiceIds) {
        $output['delivery_service_ids'] = $this->entityTypeManager->getStorage('base_product')->loadMultiple($deliveryServiceIds);
      }
    }

    return $output;
  }

  /**
   * @param \Drupal\Core\Datetime\DrupalDateTime $birthdate
   * @return int
   */
  protected function doCalculateAge(DrupalDateTime $birthdate) {
    // @todo need more clear verify on passengers with birthday near at today time.
    $today = DrupalDateTime::createFromtimestamp(time());
    $age = $today->diff($birthdate)->y;

    return $age;
  }

  protected function getHtmlClass($type) {
    return strtolower(Html::cleanCssIdentifier($type));
  }

  /**
   * Compare suppliers on the trip.
   *
   * @return bool
   */
  protected function isSameSuppliersPassengerForm() {
    $timetableResult = $this->store->get(TrainBookingManager::TIMETABLE_RESULT_KEY);
    foreach ($timetableResult as $result) {
      $supplier = $result['train_info']->getSupplier();
      $passengerForms[] = $supplier->getPassengerFormType();
    }
    if ($passengerForms[0] == $passengerForms[1]) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * Update service header after submitting form.
   *
   * @param $form
   * @param $productId
   * @param $submittedData
   * @param string $summary
   */
  protected function updateServiceHeader(&$form, $productId, $submittedData, $summary = '') {
    /** @var \Drupal\store\Entity\BaseProduct $service */
    $service = $this->entityTypeManager->getStorage('base_product')->load($productId);
    $form['title']['markup']['#markup'] = $this->getServiceHeader($service, $submittedData, $summary);
  }

  /**
   * @param \Drupal\store\Entity\BaseProduct $service
   * @param $submittedData
   * @param $summary
   * @return string
   */
  protected function getServiceHeader($service, $submittedData, $summary = '') {
    $currencyCode = $this->defaultCurrency->getUserCurrency();
    $priceFormattableMarkup = $service->getPrice()->getNumber() > 0 ? new FormattableMarkup($service->getPrice()->convert($currencyCode), []) : null;
    $pricePlaceholders = ['@price' => $priceFormattableMarkup, '@price_title' => $service->getPriceTitle()];
    $price = $this->t('@price @price_title', $pricePlaceholders, ['context' => 'Passenger Form']);
    if (!empty($submittedData)) {
      $header = '<div class="service-title selected">';
    }
    else {
      $header = '<div class="service-title">';
    }
    $header .= '<div class="title"><span>' . $service->getName() . '</span></div>';
    if ($service->getPrice()->getNumber()) {
      $header .= '<div class="price">' . $price . '</div>';
    }
    if ($summary) {
      $header .= '<div class="summary">' . $summary . '</div>';
    }

    $header .= '</div>';

    return $header;
  }

  /**
   * Update services data in storage.
   *
   * @param $productId
   * @param $submittedData
   */
  protected function orderServicesHandler($productId, $submittedData) {
    $services = $this->store->get(TrainBookingManager::SERVICES_KEY);
    /** @var \Drupal\store\Entity\BaseProduct $product */
    $product = $this->entityTypeManager->getStorage('base_product')->load($productId);
    $serviceType = $product->bundle();
    if ($services) {
      foreach ($services as $currentServiceType => $specificServices) {
        if ($serviceType == $currentServiceType) {
          if (!empty($specificServices[$productId])) {
            if (empty($submittedData)) {
              unset($services[$currentServiceType][$productId]);
            }
            elseif (!empty($submittedData)) {
              $services[$serviceType][$productId] = $this->getService($productId, $submittedData);
            }
          }
          elseif (!empty($submittedData)) {
            $services[$serviceType][$productId] = $this->getService($productId, $submittedData);
          }
        }
        elseif (!empty($submittedData)) {
          $services[$serviceType][$productId] = $this->getService($productId, $submittedData);
        }
      }
    }
    elseif (!empty($submittedData)) {
      $services[$serviceType][$productId] = $this->getService($productId, $submittedData);
    }
    $this->store->set(TrainBookingManager::SERVICES_KEY, $services);
  }

  /**
   * Return single service data with specific structure.
   *
   * @param $productId
   * @param $submittedData
   * @return array
   */
  protected function getService($productId, $submittedData) {
    /** @var \Drupal\store\Entity\BaseProduct $product */
    $product = $this->entityTypeManager->getStorage('base_product')->load($productId);
    $currencyCode = $this->defaultCurrency->getUserCurrency();
    $service = [
      'name' => $product->getName(),
      'quantity' => !empty($submittedData['quantity']) ? $submittedData['quantity'] : 1,
      'original_price' => $product->getPrice()->convert($currencyCode),
      'price' => $product->getPrice()->convert($currencyCode),
      'product' => $product,
      'data' => [['pickedData' => $submittedData]],
    ];

    return $service;
  }

  /**
   * Updates success search statistic.
   */
  protected function updateSuccessSearchStat() {
    try {
      if (($id = $this->store->get('success_search_detailed_id')) && !$this->store->get('passenger_form_success_search_stat_updated')) {
        /** @var \Drupal\train_booking\Entity\SuccessSearchDetailed $successSearch */
        $successSearch = $this->loadEntity('success_search_detailed', $id);
        $successSearch->incrementPassengerPageLoadCount()
          ->save();

        $this->store->set('passenger_form_success_search_stat_updated', true);
      }
    }
    catch (\Exception $e) {
      // Avoid from any errors because of stat
    }
  }

}
