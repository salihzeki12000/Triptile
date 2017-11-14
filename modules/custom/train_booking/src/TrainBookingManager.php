<?php

namespace Drupal\train_booking;

use Drupal\booking\BookingManagerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\store\Entity\StoreOrder;
use Drupal\store\OrderVerification;
use Drupal\store\PriceRule;
use Drupal\train_base\Entity\TrainTicket;
use Drupal\train_base\Entity\Passenger;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\salesforce\SalesforceSync;
use Drupal\salesforce\Plugin\SalesforceMappingManager;
use Drupal\train_booking\Entity\BookingStat;
use Drupal\train_provider\AvailableBookingTrainProviderInterface;
use Drupal\train_provider\TrainProviderManager;

/**
 * Class TrainBookingManager.
 *
 * @package Drupal\train_booking
 */
class TrainBookingManager extends BookingManagerBase {

  /**
   * Order type used by this booking manager.
   */
  const ORDER_TYPE = 'train_order';

  /**
   * Order item types.
   */
  const
    TICKET_ORDER_ITEM_TYPE = 'ticket',
    TAX_ORDER_ITEM_TYPE = 'tax',
    OPTIONAL_SERVICE_ORDER_ITEM_TYPE = 'optional_service',
    DELIVERY_SERVICE_ORDER_ITEM_TYPE = 'delivery_service';

  /**
   * Session store keys.
   */
  const
    SEARCH_REQUEST_KEY = 'search_request',
    SEARCH_RESULT_KEY = 'search_result',
    TIMETABLE_RESULT_KEY = 'timetable_result',
    PASSENGERS_RESULT_KEY = 'passengers_result',
    EMAIL_KEY = 'email',
    ENTITIES_SAVED_KEY = 'entities_saved',
    PASSENGERS_KEY = 'passengers',
    TRAIN_TICKETS_KEY = 'train_tickets',
    NOTES_KEY = 'notes',
    SERVICES_KEY = 'services';

  /**
   * @var \Drupal\train_base\Entity\Passenger[][]
   */
  protected $passengers;

  /**
   * @var \Drupal\train_base\Entity\TrainTicket[][]
   */
  protected $train_tickets;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\train_provider\TrainProviderManager
   */
  protected $trainProviderManager;

  /**
   * TrainBookingManager constructor.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\store\PriceRule $price_rule
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   * @param \Drupal\salesforce\SalesforceSync $salesforce_sync
   * @param \Drupal\salesforce\Plugin\SalesforceMappingManager $mapping_manager
   * @param \Drupal\store\OrderVerification $order_verification
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\train_provider\TrainProviderManager $train_provider_manager
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManager $entity_type_manager,
                              PriceRule $price_rule, LanguageManager $language_manager, SalesforceSync $salesforce_sync,
                              SalesforceMappingManager $mapping_manager, OrderVerification $order_verification, ConfigFactoryInterface $config_factory,
                              TrainProviderManager $train_provider_manager) {
    parent::__construct($current_user, $entity_type_manager, $price_rule, $language_manager, $salesforce_sync, $mapping_manager, $order_verification);
    $this->configFactory = $config_factory;
    $this->trainProviderManager = $train_provider_manager;
  }

  protected function getOrderType() {
    return static::ORDER_TYPE;
  }

  public function getTrainTickets() {
    if (!$this->train_tickets) {
      $this->train_tickets = $this->getStore()->get(static::TRAIN_TICKETS_KEY) ? : $this->createTrainTickets();
    }

    return $this->train_tickets;
  }

  /**
   * Create TrainTickets related to current booking.
   *
   * @return \Drupal\train_base\Entity\TrainTicket[][]
   */
  protected function createTrainTickets() {
    $train_tickets = [];
    $timetable_result = $this->getStore()->get(static::TIMETABLE_RESULT_KEY);
    foreach ($timetable_result as $route_key => $result) {
      /** @var \Drupal\train_provider\CoachClassInfoHolder $coachClassInfoHolder */
      $coachClassInfoHolder = $result['coach_class_info'];
      $capacity = $coachClassInfoHolder->getSeatType()->getCapacity();
      if ($capacity == 1) {
        foreach ($result['pax'] as $passenger_key => $age) {
          $train_tickets[$route_key][$passenger_key] = $this->createTrainTicket($result, $route_key);
        }
      }
      else {
        $cabinsCount = ceil(count($result['pax']) / $capacity);
        for ($i = 0; $i < $cabinsCount; $i++) {
          $train_tickets[$route_key][$i] = $this->createTrainTicket($result, $route_key);
        }
      }
    }
    $this->setTrainTickets($train_tickets);

    return $train_tickets;
  }

  /**
   * {@inheritdoc}
   */
  public function createTrainTicket($result, $routeKey) {
    /** @var \Drupal\train_provider\TrainInfoHolder $trainInfoHolder */
    $trainInfoHolder = $result['train_info'];
    $train = $trainInfoHolder->getTrain();
    $departureStation = $trainInfoHolder->getDepartureStation();
    $arrivalStation = $trainInfoHolder->getArrivalStation();
    /** @var \Drupal\train_provider\CoachClassInfoHolder $coachClassInfoHolder */
    $coachClassInfoHolder = $result['coach_class_info'];
    $coachClass = $coachClassInfoHolder->getCoachClass();
    $train_ticket = TrainTicket::create()
      ->setDepartureDateTime($result['departure_datetime'])
      ->setArrivalDateTime($result['arrival_datetime'])
      ->setDepartureStation($departureStation)
      ->setArrivalStation($arrivalStation)
      ->setLegNumber($routeKey)
      ->setTrainClass($trainInfoHolder->getTrainClass())
      ->setCoachClass($coachClass)
      ->setSeatType($coachClassInfoHolder->getSeatType())
      ->setCarServices($coachClassInfoHolder->getCarServices())
      ->setTrainNumber($trainInfoHolder->getTrainNumber());

    if ($trainInfoHolder->getChangeStation()) {
      $train_ticket->setChangeStation($trainInfoHolder->getChangeStation());
    }
    if ($train) {
      if ($train->getName()) {
        $train_ticket->setTrainName($train->getName());
      }
      if ($train->isBoardingPassRequired()) {
        $train_ticket->setBoardingPassRequired($train->isBoardingPassRequired());
      }
    }

    return $train_ticket;
  }

  /**
   * {@inheritdoc}
   */
  public function setTrainTickets(array $train_tickets) {
    if (!empty($train_tickets)) {
      $this->train_tickets = $train_tickets;
      $this->getStore()->set(static::TRAIN_TICKETS_KEY, $train_tickets);
    }
  }

  /**
   * Gets Passengers related to current booking.
   *
   * @return \Drupal\train_base\Entity\Passenger[][]
   */
  protected function getPassengers() {
    if (!$this->passengers) {
      $this->passengers = $this->getStore()->get(static::PASSENGERS_KEY) ? : $this->createPassengers();
    }

    return $this->passengers;
  }

  /**
   * {@inheritdoc}
   */
  public function createPassengers() {
    $passengers = [];
    $user = $this->getUser();
    $passengers_result = $this->getStore()->get(TrainBookingManager::PASSENGERS_RESULT_KEY);
    if (!empty($passengers_result)) {
      foreach ($passengers_result as $route_key => $result) {
        foreach ($result as $passenger_key => $passenger) {
          // We always must create new passenger, shoutout to SalesForce.
          /*$query = $this->entityQuery->get('passenger');
          $query->condition('uid', $user->id());
          $query->condition('first_name', $passenger['first_name']);
          $query->condition('last_name', $passenger['last_name']);
          $passenger_id = $query->execute();
          if (empty($passenger_id)) {
            if (!empty($passenger['dob'])) {
              $passenger['dob'] = $passenger['dob']->format(DATETIME_DATE_STORAGE_FORMAT);
            }
            $passenger_entity = $this->createPassenger($passenger);
          }
          else {
            $passenger_entity = $this->entityTypeManager->getStorage('passenger')->loadMultiple($passenger_id);
            if (is_array($passenger_entity)) {
              $passenger_entity = array_pop($passenger_entity);
            }
          }*/
          if (!empty($passenger['dob'])) {
            $passenger['dob'] = $passenger['dob']->format(DATETIME_DATE_STORAGE_FORMAT);
          }
          $passengers[$route_key][$passenger_key] = $this->createPassenger($passenger);
        }
      }
    }
    $this->setPassengers($passengers);

    return $passengers;
  }

  /**
   * Creates a passenger.
   *
   * @param array $values
   * @return \Drupal\train_base\Entity\Passenger
   */
  public function createPassenger($values = []) {
    $passenger = Passenger::create($values);

    return $passenger;
  }

  /**
   * {@inheritdoc}
   */
  public function setPassengers(array $passengers) {
    if (!empty($passengers)) {
      $this->passengers = $passengers;
      $this->getStore()->set(static::PASSENGERS_KEY, $passengers);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateOrderItems() {
    $this->doCreateOrderItems($this->getOrder());
  }

  /**
   * Creates a order items.
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   */
  protected function doCreateOrderItems(StoreOrder $order) {
    $user_currency = $this->getStore()->get(BookingManagerBase::USER_CURRENCY_KEY);
    $timetable_result = $this->getStore()->get(static::TIMETABLE_RESULT_KEY);
    $all_order_items = [];
    foreach ($timetable_result as $route_key => $result) {
      /** @var \Drupal\train_provider\CoachClassInfoHolder $coachClassInfoHolder */
      $coachClassInfoHolder = $result['coach_class_info'];
      $capacity = $coachClassInfoHolder->getSeatType()->getCapacity();
      $price = $coachClassInfoHolder->getPrice()->convert($user_currency);
      /** @var \Drupal\train_base\Entity\Supplier $supplier */
      $supplier = $result['train_info']->getSupplier();
      if ($capacity == 1) {
        $items = [];
        foreach ($result['pax'] as $age) {
          $updatedPrice = $this->priceRule->updatePrice('ticket', $price, ['age' => $age, 'supplier' => $supplier->getCode()]);
          $amount = (int)$updatedPrice['price']->getNumber();
          // @todo if amount doesn't change after implementing price rules for child, so name will be adult.
          if (empty($items[$amount])) {
            $items[$amount]['price'] = $updatedPrice;
            $items[$amount]['age'] = $age;
            $items[$amount]['quantity'] = 1;
          }
          else {
            $items[$amount]['quantity'] += 1;
          }
        }
      }
      else {
        $cabinsCount = ceil(count($result['pax']) / $capacity);
        $amount = (int)$price->getNumber();
        $items[$amount]['price']['price'] = $price;
        $items[$amount]['quantity'] = $cabinsCount;
      }
      $order_items = [];
      foreach ($items as $amount => $item) {
        $price = $item['price'];
        $quantity = $item['quantity'];
        if ($capacity == 1) {
          $name = !$supplier->isInfant($item['age']) ? $supplier->isChild($item['age']) ? 'Child' : 'Adult' : 'Infant';
        }
        else {
          $name = $this->t('Price per cabin', [], ['context' => 'Sidebar Order Details']);
        }
        $priceComponents = !(empty($price['applied_rules'])) ? implode('::', $price['applied_rules']) : '';
        $parameters = [
          'original_price' => $coachClassInfoHolder->getOriginalPrice(),
          'price' => $price['price'],
          'name' => $name,
          'price_components' => $priceComponents,
          'quantity' => $quantity,
          'leg_number' => $route_key,
        ];
        if ($coachClassInfoHolder->getProduct()) {
          $parameters['product'] = $coachClassInfoHolder->getProduct();
        }
        $order_item = $this->createOrderItem($order, static::TICKET_ORDER_ITEM_TYPE, $parameters);

        if ($this->languageManager->getCurrentLanguage()->getId() != $this->languageManager->getDefaultLanguage()->getId()) {
          $translated_name = !$supplier->isInfant($item['age']) ? $supplier->isChild($item['age'])
            ? $this->t('Child', [], ['context' => 'Order item'])
            : $this->t('Adult', [], ['context' => 'Order item'])
            : $this->t('Infant', [], ['context' => 'Order item']);
          $order_item->addTranslation($this->languageManager->getCurrentLanguage()->getId(), ['name' => $translated_name]);
        }
        $order_items[] = $order_item;
      }
      $all_order_items[$route_key] = $order_items;
    }

    // Create order items based on services products.
    $services = $this->getStore()->get(static::SERVICES_KEY);
    if ($services) {
      foreach ($services as $serviceType => $specificServices) {
        foreach ($specificServices as $service) {
          $parameters = [
            'name' => $service['name'],
            'quantity' => $service['quantity'],
            'original_price' => $service['original_price'],
            'price' => $service['price']->convert($user_currency),
            'product' => $service['product'],
            'data' => $service['data'],
          ];
          $all_order_items[$serviceType][] = $this->createOrderItem($order, $serviceType, $parameters);
        }
      }
    }

    $this->setOrderItems($all_order_items);
    $this->calculateOrderTotal($order);
    $order_subtotal = $order->getOrderTotal();

    // Implement 'order' price rule for calculating finish order total.
    $search_request = $this->getStore()->get('search_request');
    $departure_date = $search_request['legs']['1']['departure_date'];
    $departure_station_timezone = $departure_date->getTimezone();
    $today = DrupalDateTime::createFromtimestamp(time());
    $today->setTimeZone($departure_station_timezone);
    $today->setTime(0, 0);
    $order_depth = $departure_date->diff($today)->days;
    $order_total = $this->priceRule->updatePrice('order', $order_subtotal, ['order_depth' => $order_depth]);

    /** @var \Drupal\store\Price $order_total_price */
    $order_total_price = $order_total['price'];
    if (!$order_total_price->equals($order_subtotal)) {
      $order_tax = $order_total_price->subtract($order_subtotal);
      $order_item = $this->createOrderItem($order, static::TAX_ORDER_ITEM_TYPE, [
        'price' => $order_tax,
        'name' => 'Taxes',
        'price_components' => implode('::', $order_total['applied_rules']),
        'quantity' => 1,
      ]);

      if ($this->languageManager->getCurrentLanguage()->getId() != $this->languageManager->getDefaultLanguage()->getId()) {
        $translated_name = $this->t('Taxes', [], ['context' => 'Order item']);
        $order_item->addTranslation($this->languageManager->getCurrentLanguage()->getId(), ['name' => $translated_name]);
      }
      $all_order_items['order_tax'] = [$order_item];
    }
    $this->setOrderItems($all_order_items);
    $this->calculateOrderTotal($order);
  }

  /**
   * {@inheritdoc}
   */
  public function saveOrderEntities() {
    $user = $this->getUser();
    $user->save();
    $this->getStore()->set(BookingManagerBase::USER_KEY, $user);
    $passengers = $this->getPassengers();
    $tickets = $this->getTrainTickets();
    foreach ($tickets as $route_key => $tickets_on_the_route) {
      /** @var \Drupal\train_base\Entity\TrainTicket $ticket */
      foreach ($tickets_on_the_route as $ticket_key => $ticket) {
        if (!empty($passengers)) {
          $capacity = $ticket->getSeatType()->getCapacity();
          if ($capacity == 1) {
            /** @var \Drupal\train_base\Entity\Passenger $passenger */
            $passenger = $passengers[$route_key][$ticket_key];
            $passenger->setOwner($user);
            $passenger->save();
            $ticket->setPassengers([$passenger]);
          }
          else {
            $ticketPassenger = array_slice($passengers[$route_key], $capacity * $ticket_key, $capacity);
            /** @var \Drupal\train_base\Entity\Passenger $passenger */
            foreach ($ticketPassenger as $passenger_key => $passenger) {
              $passenger->setOwner($user);
              $passenger->save();
            }
            $ticket->setPassengers($ticketPassenger);
          }
        }
        $ticket->save();
        $tickets_entities[] = $ticket;
      }
    }
    $this->setPassengers($passengers);
    $this->setTrainTickets($tickets);
    $order = $this->getOrder()
      ->setTripType($this->getTripType())
      ->setOwner($user)
      ->setTickets($tickets_entities)
      ->setNotes($this->getStore()->get(static::NOTES_KEY));
    $order->save();
    $this->getStore()->set(BookingManagerBase::ORDER_KEY, $order);
    $all_order_items = $this->getOrderItems();
    foreach ($all_order_items as $route_key => $values) {
      $order_items = [];
      foreach ($values as $order_item) {
        $order_item->setOrder($order);
        $order_item->save();
        $order_items[] = $order_item;
      }
      $order_items_entities[$route_key] = $order_items;
    }
    $this->setOrderItems($order_items_entities);
    $invoice = $this->getInvoice()
      ->setOrder($order)
      ->setUser($user)
      ->setDescription($this->t('Invoice for order @order.', ['@order' => $order->getOrderNumber()]));
    $invoice->save();
    $this->setInvoice($invoice);
    $this->getStore()->set(static::ENTITIES_SAVED_KEY, true);
  }

  /**
   * Gets tripType for current search request.
   *
   * @return string
   */
  protected function getTripType() {
    $search_request = $this->getStore()->get(static::SEARCH_REQUEST_KEY);
    if (!empty($search_request)) {
      $trip_type = 'simple';
      if (isset($search_request['complex_trip']) && isset($search_request['round_trip'])) {
        if ($search_request['complex_trip'] === true && $search_request['round_trip'] === false) {
          $trip_type = 'complex';
        }
        else if ($search_request['complex_trip'] === true && $search_request['round_trip'] === true) {
          $trip_type = 'roundtrip';
        }
      }
      return $trip_type;
    }

    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function bookingPaid(StoreOrder $order) {
    $this->doBooking($order);
    parent::bookingPaid($order);
    $this->updateSuccessBookingStat($order);
    /** @var \Drupal\train_base\Entity\TrainTicket $ticket */
    foreach ($order->getTickets() as $ticket) {
      foreach ($ticket->getPassengers() as $passenger) {
        $this->salesforceBaseTrigger($passenger);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function bookingFailed(StoreOrder $order) {
    parent::bookingFailed($order);
    $this->updateFailedBookingStat($order);
    /** @var \Drupal\train_base\Entity\TrainTicket $ticket */
    foreach ($order->getTickets() as $ticket) {
      foreach ($ticket->getPassengers() as $passenger) {
        $this->salesforceBaseTrigger($passenger);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function bookingCanceled(StoreOrder $order) {
    parent::bookingFailed($order);
    $this->updateFailedBookingStat($order);
    /** @var \Drupal\train_base\Entity\TrainTicket $ticket */
    foreach ($order->getTickets() as $ticket) {
      foreach ($ticket->getPassengers() as $passenger) {
        $this->salesforceBaseTrigger($passenger);
      }
    }
  }

  /**
   * @param \Drupal\store\Entity\StoreOrder $order
   */
  protected function doBooking($order) {
    $data = $trainProviders = [];

    // We aggregate data form timetable result based on the plugin ID.
    // It allows for us call preBooking and finalizeBooking only one
    // time for each plugin. Lets to plugin make a request in order with
    // its specifications.
    $timetableResult = $this->getStore()->get(static::TIMETABLE_RESULT_KEY);
    foreach ($timetableResult as $leg => $legResult) {
      /** @var \Drupal\train_provider\TrainInfoHolder $trainInfoHolder */
      $trainInfoHolder = $timetableResult[$leg]['train_info'];
      /** @var \Drupal\train_provider\CoachClassInfoHolder $coachClassInfoHolder */
      $coachClassInfoHolder = $timetableResult[$leg]['coach_class_info'];
      $data[$coachClassInfoHolder->getPluginId()][$leg] = [
        'train_info_holder' => $trainInfoHolder,
        'coach_class_info_holder' => $coachClassInfoHolder,
      ];
    }

    // Make booking for each provider what we have in the current booking process.
    foreach ($data as $pluginId => $legsResult) {
      $trainProviders[] = $pluginId;
      $configuration = $this->getPluginConfiguration($pluginId);
      if ($configuration['booking_available']) {
        /** @var \Drupal\train_provider\TrainProviderBase $plugin */
        $plugin = $this->trainProviderManager->createInstance($pluginId, $configuration);
        if ($plugin instanceof AvailableBookingTrainProviderInterface) {
          $order->setData('TrainBookingManager1', 'automatical booking started');
          $preBookingResponse = $plugin->preBooking($legsResult, $order);
          $plugin->finalizeBooking($legsResult, $order, $preBookingResponse);
          $order->setData('TrainBookingManager2', 'automatical booking completed');
        }
      }
    }
    $order->setTrainProviders($trainProviders)->save();
  }

  /**
   * Provide methods, which can be call after finalize booking.
   *
   * @param $op
   * @param $orderId
   * @param $pickedLeg
   * @param null $ticketId
   */
  public function bookingHandler($op, $orderId, $pickedLeg, $ticketId = null) {
    /** @var \Drupal\store\Entity\StoreOrder $order */
    $order = $this->entityTypeManager->getStorage('store_order')->load($orderId);
    $bookingData = $order->getData('bookingData');
    if ($bookingData) {
      $providerBookingData = [];

      // We aggregate data form timetable result based on the plugin ID.
      // It allows for us call preBooking and finalizeBooking only one
      // time for each plugin. Lets to plugin make a request in order with
      // its specifications.
      foreach ($bookingData as $leg => $data) {
        $providerBookingData[$data['providerId']][$leg] = $data;
      }

      $pluginId = $bookingData[$pickedLeg]['providerId'];
      $data = $providerBookingData[$pluginId];
      $configuration = $this->getPluginConfiguration($pluginId);
      if ($configuration['booking_available']) {
        /** @var \Drupal\train_provider\TrainProviderBase $plugin */
        $plugin = $this->trainProviderManager->createInstance($pluginId, $configuration);
        switch ($op) {
          case 'getInfo':
            $plugin->getInfo($data, $order, $pickedLeg);
            break;
          case 'cancelBooking':
            $plugin->cancelBooking($data, $order, $pickedLeg);
            break;
          case 'cancelTicketBooking':
            // @TODO: At this time this option is allow only for BeNe train provider. Stay tuned!
            if ($pluginId == 'bene_train_provider' && $ticketId) {
              $plugin->cancelTicketBooking($data, $order, $pickedLeg, $ticketId);
            }
            break;
          case 'checkPdf':
            $plugin->checkPdf($data, $order, $pickedLeg);
            break;
        }
      }
    }
  }

  /**
   * Call train_provider checkPdf() method from BookingHandler.
   */
  public function checkPdf() {
    $storeOrderStorage = $this->entityTypeManager->getStorage('store_order');
    $query = $storeOrderStorage->getQuery();
    $query->condition('status', StoreOrder::STATUS_PROCESSING);
    $query->condition('train_provider', ['it_train_provider', 'bene_train_provider'], 'IN');
    $query->notExists('pdf_file');
    $ids = $query->execute();
    if ($ids) {
      $orders = $storeOrderStorage->loadMultiple($ids);
      /** @var \Drupal\store\Entity\StoreOrder $order */
      foreach ($orders as $order) {
        $bookingData = $order->getData('bookingData');
        if (is_array($bookingData)) {
          foreach ($bookingData as $leg => $data) {
            if ($data['status'] == 'booked' && empty($data['pdf'])) {
              // Avoid twice calling roundTrip type from single provider.
              // In BeNe this method can be call once, in further will be generate error.
              // Check pdf url again we can from dossier (getInfo).
              if ($leg == 2 && $bookingData[1]['bookingKey'] == $bookingData[2]['bookingKey']) {
                break;
              }
              $this->bookingHandler('checkPdf', $order->id(), $leg);
            }
          }
        }
      }
    }
  }

  /**
   * Get plugin configurations.
   *
   * @param string $pluginId
   * @return array
   */
  protected function getPluginConfiguration($pluginId) {
    $search_configuration = [];

    try {
      $searchRequest = $this->getStore()->get(static::SEARCH_REQUEST_KEY);

      // Prepare configurations for plugin.
      // Search configurations from search request.
      $search_configuration = [
        'adult_number' => $searchRequest['adults'],
        'child_number' => $searchRequest['children'],
        'round_trip' => $searchRequest['round_trip'],
        'complex_trip' => $searchRequest['complex_trip'],
      ];
      foreach ($searchRequest['legs'] as $leg => $legData) {
        $search_configuration['legs'][$leg]['departure_station'] = $this->loadEntity('station', $legData['departure_station']);
        $search_configuration['legs'][$leg]['arrival_station'] = $this->loadEntity('station', $legData['arrival_station']);
        $search_configuration['legs'][$leg]['departure_date'] = $legData['departure_date'];
      }
    }
    catch (\Exception $e) {

    }

    // Base common configurations
    $train_provider_configuration = $this->configFactory
      ->get('train_provider.settings')
      ->get();

    // Own plugin configurations.
    $plugin_configuration = $this->configFactory
      ->get('plugin.plugin_configuration.train_provider.' . $pluginId)
      ->get();

    // Merge all configurations
    $configuration = array_merge($search_configuration, $train_provider_configuration, $plugin_configuration);

    return $configuration;
  }

  /**
   * Updates statistic of failed bookings.
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   */
  protected function updateFailedBookingStat(StoreOrder $order) {
    try {

      if (($id = $this->getStore()->get('success_search_detailed_id')) && !$this->getStore()->get('failed_booking_stat_updated')) {

        // Success searches
        /** @var \Drupal\train_booking\Entity\SuccessSearchDetailed $successSearch */
        $successSearch = $this->entityTypeManager->getStorage('success_search_detailed')->load($id);
        $successSearch->incrementFailedBookingCount()
          ->save();

        // Booking stat
        $bookingStatStorage = $this->entityTypeManager->getStorage('booking_stat');
        $search_request = $this->getStore()->get('search_request');
        $entity_ids = $bookingStatStorage->getQuery()
          ->condition('departure_station', $successSearch->getDepartureStation()->id())
          ->condition('arrival_station', $successSearch->getArrivalStation()->id())
          ->execute();
        if (!empty($entity_ids)) {
          /** @var \Drupal\train_booking\Entity\BookingStat $bookingStat */
          $bookingStat = $bookingStatStorage->load(reset($entity_ids));
          $bookingStat->incrementFailedBookingCount();
        }
        else {
          $bookingStat = BookingStat::create([
            'departure_station' => $successSearch->getDepartureStation(),
            'arrival_station' => $successSearch->getArrivalStation(),
            'failed_booking_count' => 1,
          ]);
        }
        isset($search_request['complex_trip']) && $search_request['complex_trip'] == true ? $bookingStat->incrementComplexTripCount() : $bookingStat->incrementOneWayTripCount();
        $bookingStat->save();

        $this->getStore()->set('failed_booking_stat_updated', true);
      }
    }
    catch (\Exception $e) {
      // Avoid from any errors because of stat
    }
  }

  /**
   * Updates statistic of success bookings.
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   */
  protected function updateSuccessBookingStat(StoreOrder $order) {
    try {

      if (($id = $this->getStore()->get('success_search_detailed_id')) && !$this->getStore()->get('success_booking_stat_updated')) {

        // Success search
        /** @var \Drupal\train_booking\Entity\SuccessSearchDetailed $successSearch */
        $successSearch = $this->entityTypeManager->getStorage('success_search_detailed')->load($id);
        if ($this->getStore()->get('failed_booking_stat_updated')) {
          $successSearch->decrementFailedBookingCount();
        }
        $successSearch->incrementSuccessBookingCount()
          ->incrementTicketCount(count($order->getTickets()))
          ->save();

        // Booking stat
        $bookingStatStorage = $this->entityTypeManager->getStorage('booking_stat');
        $search_request = $this->getStore()->get('search_request');
        $entity_ids = $bookingStatStorage->getQuery()
          ->condition('departure_station', $successSearch->getDepartureStation()->id())
          ->condition('arrival_station', $successSearch->getArrivalStation()->id())
          ->execute();
        if (!empty($entity_ids)) {
          /** @var \Drupal\train_booking\Entity\BookingStat $bookingStat */
          $bookingStat = $bookingStatStorage->load(reset($entity_ids));
          $bookingStat->incrementSuccessBookingCount();
          if ($this->getStore()->get('failed_booking_stat_updated')) {
            $bookingStat->decrementFailedBookingCount();
          }
          else {
            isset($search_request['complex_trip']) && $search_request['complex_trip'] == true ? $bookingStat->incrementComplexTripCount() : $bookingStat->incrementOneWayTripCount();
          }
        }
        else {
          $bookingStat = BookingStat::create([
            'departure_station' => $successSearch->getDepartureStation(),
            'arrival_station' => $successSearch->getArrivalStation(),
            'success_booking_count' => 1,
          ]);
          isset($search_request['complex_trip']) && $search_request['complex_trip'] == true ? $bookingStat->incrementComplexTripCount() : $bookingStat->incrementOneWayTripCount();
        }
        $bookingStat->incrementTicketCount(count($order->getTickets()));
        $bookingStat->save();

        $this->getStore()->set('success_booking_stat_updated', true);
      }
    }
    catch (\Exception $e) {
      // Avoid from any errors because of stat
    }
  }

}
