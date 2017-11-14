<?php

namespace Drupal\train_booking\Form;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use Drupal\address\FieldHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\train_booking\TrainBookingManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\payment\Form\PaymentFormTrait;
use Drupal\booking\BookingManagerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\store\Price;

/**
 * Class PaymentForm.
 *
 * @package Drupal\train_booking\Form
 */
class PaymentForm extends TrainBookingBaseForm {

  use PaymentFormTrait {
    configFactory as traitConfigFactory;
  }

  /**
   * PaymentForm constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  public function __construct(ContainerInterface $container) {
    parent::__construct($container);

    $this->paymentMethodManager = $container->get('plugin.manager.payment.payment_method');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'train_booking_payment_form';
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
    $user_currency = $this->defaultCurrency->getUserCurrency();
    $invoice = $this->bookingManager->getInvoice();
    if ($this->store->get(BookingManagerBase::USER_CURRENCY_KEY) != $user_currency) {
      $this->store->set(BookingManagerBase::USER_CURRENCY_KEY, $user_currency);
      $this->bookingManager->updateOrderItems();
      $invoice = $this->bookingManager->convertInvoiceAmount();
      if ($this->store->get(TrainBookingManager::ENTITIES_SAVED_KEY)) {
        $this->recalculateOrderDetails();
      }
    }

    if ($invoice->isPaid()) {
      drupal_set_message($this->t('The order has already been paid.', [], ['context' => 'Order has been paid']), 'warning');
      return new RedirectResponse($this->getRedirectUrl());
    }

    $timetable_result = $this->store->get(TrainBookingManager::TIMETABLE_RESULT_KEY);
    $passengers_result = $this->store->get(TrainBookingManager::PASSENGERS_RESULT_KEY);
    $passenger_email = $this->store->get(TrainBookingManager::EMAIL_KEY);

    if (isset($timetable_result)) {
      $form['#attached']['library'][] = 'train_booking/open-ticket';
      $form['#attached']['library'][] = 'train_booking/payment-form';
      $form['#attached']['library'][] = 'train_booking/scroll-to-payment-form';
      $form['#cache'] = ['max-age' => 0];
      $form['main'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'route-legs-wrapper',
          ],
          'data-storage-expiration-date' => $this->store->getSessionExpirationTime(),
        ],
      ];
      $form['main']['route_legs'] = [
        '#type' => 'container',
      ];
      foreach ($timetable_result as $route_key => $result) {
        // If no result display message and go to home page.
        if (empty($result)) {
          if ($link = $this->getSearchLink()) {
            drupal_set_message($this->t('Session has been expired. @link', ['@link' => $link], ['context' => 'Session expired']), 'warning');
          }
          else {
            drupal_set_message($this->t('Session has been expired.', [], ['context' => 'Session expired']), 'warning');
          }
          return new RedirectResponse($this->getRedirectUrl());
        }
        $form['main']['route_legs'][$route_key]['route_leg_info'] = $this->generateRouteLegInfo($route_key);
        if (isset($passengers_result[$route_key])) {
          $form['main']['route_legs'][$route_key]['all_passengers'] = $this->generatePassengersInfo($passengers_result[$route_key]);
        }
      }
      $form['main']['additional_sidebar_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'additional-sidebar-wrapper',
          ]
        ],
      ];
      $form['main']['additional_sidebar_wrapper']['sidebar'] = $this->generateSidebarInfo();
      $form['main']['payment_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'payment-form',
        ],
      ];

      $form['main']['payment_wrapper']['payment'] = $this->buildPaymentForm([], $form_state, $invoice);

      // We already have some data from PassengerForm.
      // @TODO Refactoring needed
      foreach ($form['main']['payment_wrapper']['payment'] as $plugin_id => &$subform) {
        $passengerData = $this->store->get(TrainBookingManager::PASSENGERS_RESULT_KEY);
        $passenger = reset($passengerData[1]);
        if (isset($subform['billing_profile']['email'])) {
          $subform['billing_profile']['email']['#type'] = 'value';
          $subform['billing_profile']['email']['#value'] = $passenger_email;
          unset($subform['billing_profile']['email']['#title']);
          unset($subform['billing_profile']['email']['#default_value']);
        }
        if (isset($subform['billing_profile'][FieldHelper::getPropertyName(AddressField::GIVEN_NAME)], $passenger['first_name'])) {
          $subform['billing_profile'][FieldHelper::getPropertyName(AddressField::GIVEN_NAME)]['#type'] = 'value';
          $subform['billing_profile'][FieldHelper::getPropertyName(AddressField::GIVEN_NAME)]['#value'] = $passenger['first_name'];
          unset($subform['billing_profile'][FieldHelper::getPropertyName(AddressField::GIVEN_NAME)]['#title']);
          unset($subform['billing_profile'][FieldHelper::getPropertyName(AddressField::GIVEN_NAME)]['#default_value']);
        }
        if (isset($subform['billing_profile'][FieldHelper::getPropertyName(AddressField::FAMILY_NAME)], $passenger['last_name'])) {
          $subform['billing_profile'][FieldHelper::getPropertyName(AddressField::FAMILY_NAME)]['#type'] = 'value';
          $subform['billing_profile'][FieldHelper::getPropertyName(AddressField::FAMILY_NAME)]['#value'] = $passenger['last_name'];
          unset($subform['billing_profile'][FieldHelper::getPropertyName(AddressField::FAMILY_NAME)]['#title']);
          unset($subform['billing_profile'][FieldHelper::getPropertyName(AddressField::FAMILY_NAME)]['#default_value']);
        }
        // TODO Get rid of such dirty hardcode.
        if (in_array($plugin_id, ['giropay', 'nl_banks', 'at_banks', 'safetypay', 'paysera_wallet'])) {
          unset($subform['billing_profile']['#title']);
          $subform['billing_profile']['#attributes']['class'][] = 'fieldset-no-title';
        }
      }

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

    $this->trainBookingLogger->logLastStep($this->store->getSessionId(), 4);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->validatePaymentForm($form['main']['payment_wrapper']['payment'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->store->get(TrainBookingManager::ENTITIES_SAVED_KEY)) {
      $this->bookingManager->saveOrderEntities();
    }

    $invoice = $form_state->get('invoice');
    $plugin = $this->getPaymentMethodPlugin($form_state->getValue('payment_method'), $invoice);
    $route_params = [
      'invoice' => $invoice->id(),
      'payment_method' => $plugin->getBaseId(),
    ];
    $this->setSuccessUrl(Url::fromRoute('train_booking.payment.success', $route_params))
      ->setCancelUrl(Url::fromRoute('train_booking.payment.canceled', $route_params))
      ->setFailUrl(Url::fromRoute('train_booking.payment.fail', $route_params));

    $this->submitPaymentForm($form['main']['payment_wrapper']['payment'], $form_state);
    $this->trainBookingLogger->logPaymentForm($this->store->getSessionId(), [
      'payment_method' => $plugin->getBaseId(),
      'paid_amount' => $invoice->getAmount()->convert('USD')->getNumber(),
      'order_number' => $this->bookingManager->getOrder()->getOrderNumber(),
    ]);
  }

  /**
   * Updates success search statistic.
   */
  protected function updateSuccessSearchStat() {
    try {
      if (($id = $this->store->get('success_search_detailed_id')) && !$this->store->get('payment_form_success_search_stat_updated')) {
        /** @var \Drupal\train_booking\Entity\SuccessSearchDetailed $successSearch */
        $successSearch = $this->loadEntity('success_search_detailed', $id);
        $successSearch->incrementPaymentPageLoadCount()
          ->save();

        $this->store->set('payment_form_success_search_stat_updated', true);
      }
    }
    catch (\Exception $e) {
      // Avoid from any errors because of stat
    }
  }

}
