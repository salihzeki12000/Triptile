<?php

namespace Drupal\train_booking\Form;

use Drupal\booking\BookingManagerBase;
use Drupal\booking\Form\BookingBaseForm;
use Drupal\train_booking\TrainBookingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
Use Drupal\Core\Link;
use Drupal\Core\Url;

abstract class TrainBookingBaseForm extends BookingBaseForm {

  const COLLECTION_NAME = 'train_booking',
    RU_PASSENGER_FORM = 'rzd_passenger_form';

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * @var \Drupal\train_booking\TrainBookingManager
   */
  protected $bookingManager;

  /**
   * @var \Drupal\train_provider\TrainSearcher
   */
  protected $trainSearcher;

  /**
   * TrainBookingBaseForm constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  public function __construct(ContainerInterface $container) {
    parent::__construct($container);

    $this->entityQuery = $container->get('entity.query');
    $this->bookingManager = $container->get('train_booking.train_booking_manager');
    $this->trainSearcher = $container->get('train_provider.train_searcher');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionName() {
    return static::COLLECTION_NAME;
  }

  /**
   * @return bool
   */
  protected function isComplexTrip() {
    $search_request = $this->store->get('search_request');
    if (isset($search_request['complex_trip']) && $search_request['complex_trip'] == true) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * @return bool
   */
  protected function isRoundTrip() {
    $search_request = $this->store->get(TrainBookingManager::SEARCH_REQUEST_KEY);
    if (isset($search_request['round_trip']) && $search_request['round_trip'] == true) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * Converts all order details and save them.
   */
  protected function recalculateOrderDetails() {
    $user_currency = $this->store->get(BookingManagerBase::USER_CURRENCY_KEY);
    $oder_items = $this->bookingManager->getOrderItems();
    foreach ($oder_items as $route_order_items) {
      /** @var \Drupal\store\Entity\OrderItem $order_item */
      foreach ($route_order_items as $order_item) {
        $price = $order_item->getPrice();
        $order_item->setPrice($price->convert($user_currency));
        $order_item->save();
      }
    }
    $order = $this->bookingManager->getOrder();
    $price = $order->getOrderTotal();
    $order->setOrderTotal($price->convert($user_currency));
    $order->save();
    $invoice = $this->bookingManager->getInvoice();
    $price = $invoice->getAmount();
    $invoice->setAmount($price->convert($user_currency));
    $invoice->save();
  }

  /**
   * Generates renderable array to display leg info.
   *
   * @param int $route_key
   * @return array
   */
  protected function generateRouteLegInfo($route_key) {
    $searchRequest = $this->store->get('search_request');
    $timetableResult = $this->store->get(TrainBookingManager::TIMETABLE_RESULT_KEY)[$route_key];
    /** @var \Drupal\train_provider\CoachClassInfoHolder $coachClassInfo */
    $coachClassInfo = $timetableResult['coach_class_info'];

    return [
      '#theme' => 'route_leg_info',
      '#departure_station' => $timetableResult['train_info']->getDepartureStation(),
      '#arrival_station' => $timetableResult['train_info']->getArrivalStation(),
      '#train_info' => $timetableResult['train_info'],
      // @todo Change when capacity is added.
      '#count_of_tickets' => $searchRequest['adults'] + $searchRequest['children'],
      '#coach_class_info' => $coachClassInfo,
      '#departure_datetime' => $timetableResult['departure_datetime'],
      '#arrival_datetime' => $timetableResult['arrival_datetime'],
    ];
  }

  /**
   * Generates renderable array to display sidebar info.
   *
   * @return array
   */
  protected function generateSidebarInfo() {
    $form = [
      '#theme' => 'order_details',
      '#order' => $this->bookingManager->getOrder(),
      '#order_items' => $this->bookingManager->getOrderItems(),
      '#complex_trip' => $this->isComplexTrip(),
      '#round_trip' => $this->isRoundTrip(),
      '#confidence_block' => $this->config('train_booking.settings')->get('confidence_block'),
      '#coach_class_info' => $this->store->get(TrainBookingManager::TIMETABLE_RESULT_KEY)
    ];

    return $form;
  }

  /**
   * Generates renderable array to display passenger info.
   *
   * @param $passengers
   * @return array
   */
  protected function generatePassengersInfo($passengers) {
    $form = [
      '#theme' => 'passengers_info',
      '#passengers' => $passengers,
    ];

    return $form;
  }

  /**
   * Add new stations to the list of user popular stations or increment count of searches of existing station.
   *
   * @param array $stations
   */
  protected function updateUserPopularStations($stations) {
    $user_popular_stations = $this->userPrivateTempStore->get('user_popular_stations');
    foreach ($stations as $station) {
      if (isset($user_popular_stations[$station])) {
        $user_popular_stations[$station]++;
      }
      else {
        $user_popular_stations[$station] = 1;
      }
    }
    $this->userPrivateTempStore->set('user_popular_stations', $user_popular_stations);
  }

  /**
   * Return array of user popular stations. Sorting by the most popular first.
   *
   * @return array
   */
  protected function getUserPopularStations() {
    if (php_sapi_name() !== 'cli') {
      $config = $this->configFactory->get('train_booking.settings');
      $limit = $config->get('user_popular_stations_limit');
      $user_popular_stations = $this->userPrivateTempStore->get('user_popular_stations');
      if (!empty($user_popular_stations) && $limit >= 1) {
        arsort($user_popular_stations);
        return array_slice(array_keys($user_popular_stations), 0, $limit);
      }
      else {
        return [];
      }
    }
    else {
      return [];
    }
  }

  public function getPassengerTitle($title) {
    switch ($title) {
      case 'mr':
        $output = $this->t('Mr.', [], ['context' => 'Booking Passenger Title']);
        break;
      case 'mrs':
        $output = $this->t('Mrs.', [], ['context' => 'Booking Passenger Title']);
        break;
      case 'miss':
        $output = $this->t('Miss', [], ['context' => 'Booking Passenger Title']);
        break;
      default:
        $output = '';
    }

    return $output;
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
        '#prefix' => '<div class="container-wrapper">',
        '#suffix' => '</div>',
        '#theme' => 'status_messages',
        // @todo Improve when https://www.drupal.org/node/2278383 lands.
        // Is available in Drupal 8.4
        '#message_list' => $drupal_messages,
        '#status_headings' => [
          'error' => t('Error message', [], ['context' => 'Passenger Form']),
        ],
      ];
    }

    return $output;
  }

  /**
   * If Generate link on the search. Use parameters from search request.
   *
   * @param array $searchRequest
   * @return \Drupal\Core\GeneratedLink|null The link HTML markup.
   * The link HTML markup.
   */
  protected function getSearchLink($searchRequest = null) {
    $link = null;
    $searchRequest = $searchRequest ? : $this->userPrivateTempStore->get('search_request');
    if ($searchRequest) {
      // Prepare parameters.
      $parameters = $this->getParametersFromSearchRequest($searchRequest);

      // Create Url.
      $language = \Drupal::languageManager()->getCurrentLanguage();
      $url = Url::fromRoute('<front>', [], ['query' => $parameters, 'language' => $language]);
      $url->setAbsolute(TRUE);

      // Create Link.
      $link = Link::fromTextAndUrl($this->t('Go back to the search.', [], ['context' => 'Train booking form']), $url)->toString();
    }

    return $link;
  }

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

  /**
   * Get redirect url for TrainBooking forms.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   */
  protected function getRedirectUrl() {
    return Url::fromRoute($this->getHomeRoute())->toString();
  }

  // We need to update CoachClassInfoHolder price, if user currency has changed.
  protected function updateSearchResult() {
    $userCurrency = $this->defaultCurrency->getUserCurrency();
    $searchResult = $this->store->get(TrainBookingManager::SEARCH_RESULT_KEY);
    /** @var \Drupal\train_provider\RouteInfoHolder $route */
    foreach ($searchResult as $route) {
      /** @var \Drupal\train_provider\TrainInfoHolder $train */
      foreach ($route->getTrains() as $train) {
        /** @var \Drupal\train_provider\CoachClassInfoHolder $coachClass */
        foreach ($train->getCoachClasses() as $coachClass) {
          $coachClass->setPrice($coachClass->getPrice()->convert($userCurrency));
        }
      }
    }
    $this->store->set(TrainBookingManager::SEARCH_RESULT_KEY, $searchResult);
  }

  // We need to update CoachClassInfoHolder price, if user currency has changed.
  protected function updateTimetableResult() {
    $userCurrency = $this->defaultCurrency->getUserCurrency();
    $timetableResult = $this->store->get(TrainBookingManager::TIMETABLE_RESULT_KEY);
    /** @var \Drupal\train_provider\RouteInfoHolder $route */
    foreach ($timetableResult as $route) {
      /** @var \Drupal\train_provider\CoachClassInfoHolder $coachClass */
      $coachClass = $route['coach_class_info'];
      $coachClass->setPrice($coachClass->getPrice()->convert($userCurrency));
    }
    $this->store->set(TrainBookingManager::TIMETABLE_RESULT_KEY, $timetableResult);
  }

}
