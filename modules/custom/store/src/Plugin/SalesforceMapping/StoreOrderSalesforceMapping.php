<?php

namespace Drupal\store\Plugin\SalesforceMapping;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\master\Master;
use Drupal\salesforce\Annotation\SalesforceMapping;
use Drupal\salesforce\Plugin\SalesforceMapping\SalesforceMappingBase;
use Drupal\salesforce\SalesforceException;
use Drupal\salesforce\SalesforceSync;
use Drupal\salesforce\SelectQuery;
use Drupal\store\Entity\StoreOrder;
use Drupal\store\Entity\StoreOrderInterface;
use Drupal\train_base\Entity\Station;
use Drupal\train_base\Entity\TrainTicket;

/**
 * Class StoreOrderSalesforceMapping
 *
 * @SalesforceMapping(
 *   id = "store_order_opportunity",
 *   label = @Translation("Mapping of Store order to Opportunity"),
 *   entity_type_id = "store_order",
 *   salesforce_object = "Opportunity",
 *   entity_operations = {"update", "delete"},
 *   object_operations = {"update", "delete"},
 *   priority = "drupal"
 * )
 */
class StoreOrderSalesforceMapping extends SalesforceMappingBase {

  const TRAIN_TICKET_RECORD_TYPE = 'Train ticket';

  const ORDER2_RAIL_TICKET_RECORD_TYPE = 'Rail / bus ticket';

  const
    ORDER2_LOGIST_EMAIL = 'reservations@firebirdtours.com',
    ORDER2_ACCOUNT_EMAIL = 'reservations@firebirdtours.com';

  const
    ORDER2_E_TICKET_TICKET_TYPE = 'E-ticket',
    ORDER2_PAPER_TICKET_TICKET_TYPE = 'Paper ticket',
    ORDER2_BOARDING_PASS_TICKET_TYPE = 'Boarding pass';

  const
    STAGE_PROCESSING = 'Processing',
    STAGE_BOOKED = 'Booked',
    STAGE_FRAUD_ALERT = 'Fraud Alert',
    STAGE_VERIFICATION = 'Verification',
    STAGE_FRAUD = 'Fraud',
    STAGE_REFUND_REQUESTED = 'Refund requested',
    STAGE_CANCELED = 'Canceled',
    STAGE_MODIFICATION_REQUESTED = 'Modification request',
    STAGE_MODIFYING = 'Modifying',
    STAGE_MODIFIED = 'Modified',
    STAGE_CLARIFICATIONS = 'Clarifications',
    STAGE_SOLD_OUT = 'Sold out';

  protected static $statusStageMapping = [
    StoreOrder::STATUS_PROCESSING => self::STAGE_PROCESSING,
    StoreOrder::STATUS_BOOKED => self::STAGE_BOOKED,
    StoreOrder::STATUS_FRAUD_ALERT => self::STAGE_FRAUD_ALERT,
    StoreOrder::STATUS_VERIFICATION => self::STAGE_VERIFICATION,
    StoreOrder::STATUS_FRAUD => self::STAGE_FRAUD,
    StoreOrder::STATUS_REFUND_REQUESTED => self::STAGE_REFUND_REQUESTED,
    StoreOrder::STATUS_CANCELED => self::STAGE_CANCELED,
    StoreOrder::STATUS_MODIFICATION_REQUESTED => self::STAGE_MODIFICATION_REQUESTED,
    StoreOrder::STATUS_MODIFYING => self::STAGE_MODIFYING,
    StoreOrder::STATUS_MODIFIED => self::STAGE_MODIFIED,
    StoreOrder::STATUS_CLARIFICATIONS => self::STAGE_CLARIFICATIONS,
    StoreOrder::STATUS_SOLD_OUT => self::STAGE_SOLD_OUT,
  ];

  /**
   * {@inheritdoc}
   */
  public function export() {
    $opportunity = parent::export();

    /** @var \Drupal\store\Entity\StoreOrder $order */
    $order = $this->getEntity();
    if (!$order->getData('Order2 exported')) {
      try {
        $this->createOrder2($opportunity);
        $order->setData('Order2 exported', true)->save();
      }
      catch (\Exception $exception) {
        // Since oppty already created but it's id is not on mapping object we
        // try to suppress any exceptions to avoid from leaving oppty not mapped.
        watchdog_exception('salesforce', $exception);
      }
    }

    return $opportunity;
  }

  /**
   * {@inheritdoc}
   */
  public function getImportFields() {
    $fields = [
      'AccountId',
      'StageName',
      'Train_departure__c',
    ];

    // @todo Fields that were in cluster. Do we need to import them?
    // Site_link__c - order visibility.

    return array_merge(parent::getImportFields(), $fields);
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\store\Entity\StoreOrder $order
   */
  protected function doImport(EntityInterface $order, \stdClass $record) {
    $userMappingObject = $this->assureImport($record->AccountId, 'user_account');
    if (!$userMappingObject || !$userMappingObject->getMappedEntityId()) {
      throw new SalesforceException('Order owner has not been imported yet.');
    }

    $order->setOwnerId($userMappingObject->getMappedEntityId());
    if ($status = $this->salesforceToLocalOrderStatus($record->StageName)) {
      $order->setStatus($status);
    }
    if ($record->Train_departure__c) {
      $order->setTicketIssueDate(new DrupalDateTime($record->Train_departure__c));
    }
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\store\Entity\StoreOrder $order
   */
  protected function doExport(EntityInterface $order, \stdClass $record) {
    $user_mapping_object = $this->assureExport($order->getOwner(), 'user_account');
    if (!$user_mapping_object || !$user_mapping_object->getRecordId()) {
      throw new SalesforceException('Order owner has not been exported yet.');
    }

    // Base info.
    $close_date = new DrupalDateTime(date('c', $order->getCreatedTime()), new \DateTimeZone('UTC'));
    $record->ExtId__c           = $order->getHash();
    $record->AccountId          = $user_mapping_object->getRecordId();
    $record->Site__c            = $order->getSiteCode();
    $record->Validation_pass__c = true;
    $record->CurrencyIsoCode    = $order->getOrderTotal()->getCurrencyCode();
    $record->Amount             = $order->getOrderTotal()->getNumber();
    $record->Name               = $this->opportunityName($order);
    $record->CloseDate          = $close_date->format('c');
    $record->Language__c        = $order->language()->getId();
    $record->order_reference__c = $order->getOrderNumber();
    $record->order_status__c    = $order->getStatus();
    $record->Site_request__c    = true;
    $record->is_eticket__c      = true;
    $record->StageName          = $this->localToSalesforceOrderStatus($order->getStatus());
    $record->Email__c           = $order->getOwner()->getEmail();
    $record->Description        = $order->getNotes();

    if (!empty($order->getTicketIssueDate())) {
      $record->Train_departure__c = $order->getTicketIssueDate()->format(DATETIME_DATE_STORAGE_FORMAT);
    }

    if (!empty($order->getNotes())) {
      $record->Description      = $order->getNotes();
    }

    if (!$this->mappingObject->getRecordId()) {
      $record->RecordTypeId     = $this->getRecordTypeId($order);
    }

    $record = $this->exportCustomerInfo($order, $record);
    $record = $this->exportTicketsInfo($order, $record);
    $record = $this->exportServicesInfo($order, $record);

    $suppliers = [];
    $tickets =  $order->getTickets();
    /** @var \Drupal\train_base\Entity\TrainTicket $ticket */
    foreach ($tickets as $ticket) {
      $supplier = $ticket->getCoachClass()->getSupplier();
      $suppliers[$supplier->getCode()] = $supplier->getName();
    }

    if (count($suppliers)<2) {
      $record->Train_supplier__c = implode(',', $suppliers);
    } else {
      $record->Train_supplier__c = current($suppliers);
      $record->Train_supplier_2__c = end($suppliers);
    }

    $record->supplier_code__c = implode(',', array_keys($suppliers));

    if ($trackTicketDownload = $order->getData('track_ticket_download')) {
      $record->Ticket_downloaded__c = true;
      $record->Ticket_downloaded_details__c = implode("\n", $trackTicketDownload);
    }

    // @todo Related logic hasn't been implemented yet.
    // $record->Cancel_request__c = $order->cancelRequested();
    // $record->Device__c = $order->getDevice();
    // $record->Device_OS__c = $order->getDeviceOs();
    // $record->Screen_res__c = $order->getScreenResolution();
    // $record->Transaction_descriptor__c = $transaction->getDescriptor(); // soft descriptor
    // $record->Save_search__c = $order->getSaveSearch();
    // $record->E_ticket_booking_number__c = $order->getEticketBookingNumber();
    // $record->Modification_request__c

    // @todo Do we still need this fields?
    // $record->payment_status__c = $order->getPaymentStatus();
    // Delivery
    // $record->Delivery_information__c
    // $record->Delivery_status__c = 'Requested';
    // $record->order_error__c

    return $record;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryConditions() {
    $conditions = [];
    $conditions[] = [
      'field' => 'Site__c',
      'value' => "'" . Master::siteCode() . "'",
      'operator' => '=',
    ];
    $id = array_search(static::TRAIN_TICKET_RECORD_TYPE, $this->getRecordTypes());
    $conditions[] = [
      'field' => 'RecordTypeId',
      'value' => "'" . $id . "'",
      'operator' => '=',
    ];
    // $conditions[] = [
    //   'field' => 'Site_publish__c',
    //   'value' => 'FALSE',
    //   'operator' => '=',
    // ];

    return $conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function isSyncAllowed($action) {
    if (!$this->mappingObject) {
      throw new SalesforceException('Salesforce mapping object is not set.');
    }

    return $action == SalesforceSync::SYNC_ACTION_DELETE || $this->mappingObject->getMappedEntityId();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    return StoreOrder::create([
      'type' => 'train_order'
    ]);
  }

  /**
   * Generates opportunity name.
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @return string
   */
  protected function opportunityName(StoreOrder $order) {
    return 'Ticket order ' . $order->getOrderNumber();
  }

  /**
   * Gets appropriate opportunity record type id.
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @return string
   */
  protected function getRecordTypeId(StoreOrder $order) {
    return array_search(static::TRAIN_TICKET_RECORD_TYPE, $this->getRecordTypes());
  }

  /**
   * Adds customer info to the opportunity.
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param \stdClass $record
   * @return \stdClass
   */
  protected function exportCustomerInfo(StoreOrder $order, \stdClass $record) {
    $customer_profile = null;
    $address = null;
    $card_details = null;
    $transaction = null;
    $geoip_data = null;
    /** @var \Drupal\store\Entity\Invoice $invoice */
    foreach ($order->getInvoices() as $invoice) {
      if ($invoice->isPaid()) {
        // Below condition is set for case with Simple merchant when customer profile may not exist
        if ($customer_profile = $invoice->getCustomerProfile()) {
          $address = $customer_profile->getAddress();
          /** @var \Drupal\payment\Entity\Transaction $invoice_transaction */
          foreach ($invoice->getTransactions() as $invoice_transaction) {
            if ($invoice_transaction->isSuccess()) {
              $transaction = $invoice_transaction;
              $card_details = sprintf("Transaction: %s\n%s %s\n%s=%s=%s=\n%s\n%s\n%s %s %s\n%s\n%s\n",
                $transaction->id(), $transaction->getAmount()->getNumber(),
                $transaction->getAmount()->getCurrencyCode(), $address->getGivenName(),
                $address->getAdditionalName(), $address->getFamilyName(), $customer_profile->getPhoneNumber(),
                $address->getAddressLine1(), $address->getLocality(), $address->getAdministrativeArea(),
                $address->getPostalCode(), $address->getCountryCode(), $customer_profile->getEmail()
              );

              $geoip_data = \Drupal::service('master.maxmind')->getInfoByIp($transaction->getIPAddress());

              break;
            }
          }
        }

        break;
      }
    }

    if ($customer_profile) {
      $record->CC_country__c    = $address->getCountryCode();
    }

    if ($card_details) {
      $record->card_details__c  = substr($card_details, 0, 255);
    }

    if ($transaction) {
      $record->ip_address__c    = $transaction->getIPAddress();
      if ($geoip_data) {
        $record->ip_country__c  = $geoip_data['country_code'];
        $record->ip_city__c     = $geoip_data['city'];
        $record->ip_info__c     = '';
        foreach ($geoip_data as $key => $value) {
          $record->ip_info__c   .= sprintf("%s: %s\n", $key, $value);
        }
      }
    }

    return $record;
  }

  /**
   * Adds ticket info to the opportunity.
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param \stdClass $record
   * @return \stdClass
   */
  protected function exportTicketsInfo(StoreOrder $order, \stdClass $record) {
    $boarding_pass = false;
    $boarding_pass_instructions = [];
    $start_date = $end_date = 0;
    $forwardCarNumbers = $returnCarNumbers = [];
    $depStation1 = $depStation2 = $arrStation1 = $arrStation2 = null;
    $depAddress1 = $depAddress2 = $arrAddress1 = $arrAddress2 = null;
    $chnStation1 = $chnStation2 = null;
    $ticketsInfo = [];
    $travelers = [];
    $pax = 0;
    $passengerCitizenships = [];
    /** @var \Drupal\train_base\Entity\TrainTicket $ticket */
    foreach ($order->getTickets() as $ticket) {
      $leg = $ticket->getLegNumber();
      $ticketDepStation = $ticket->getDepartureStation();
      $ticketArrStation = $ticket->getArrivalStation();
      $ticketChnStation = $ticket->getChangeStation();

      $boarding_pass = $boarding_pass || $ticket->isBoardingPassRequired();
      if ($ticket->isBoardingPassRequired()) {
        if ($ticketChnStation) {
          $instruction = sprintf('#%s %s--%s--%s', $ticket->getTrainNumber(), $ticketDepStation, $ticketChnStation, $ticketArrStation);
        }
        else {
          $instruction = sprintf('#%s %s--%s', $ticket->getTrainNumber(), $ticketDepStation, $ticketArrStation);
        }
        if (end($boarding_pass_instructions) != $instruction) {
          $boarding_pass_instructions[] = $instruction;
        }
      }

      // Start and End dates.
      if (!$start_date || ($ticket->getDepartureDateTime() && $start_date > $ticket->getDepartureDateTime()->getTimestamp())) {
        $start_date = $ticket->getDepartureDateTime()->getTimestamp();
      }
      if (!$end_date || ($ticket->getArrivalDateTime() && $end_date < $ticket->getArrivalDateTime()->getTimestamp())) {
        $end_date = $ticket->getArrivalDateTime()->getTimestamp();
      }

      if ($leg == 1) {
        // Train numbers.
        $record->Train_1__c = $ticket->getTrainNumber();

        // Car numbers.
        $forwardCarNumbers[$ticket->getCoachNumber()] = $ticket->getCoachNumber();

        // Stations.
        $depStation1 = $ticketDepStation;
        $depAddress1 = $this->getStationAddress($depStation1);
        $arrStation1 = $ticketArrStation;
        $arrAddress1 = $this->getStationAddress($arrStation1);
        $chnStation1 = $ticketChnStation;
      }
      elseif ($leg == 2) {
        // Train numbers.
        $record->Train_2__c = $ticket->getTrainNumber();

        // Car numbers.
        $returnCarNumbers[$ticket->getCoachNumber()] = $ticket->getCoachNumber();

        // Stations.
        $depStation2 = $ticketDepStation;
        $depAddress2 = $this->getStationAddress($depStation2);
        $arrStation2 = $ticketArrStation;
        $arrAddress2 = $this->getStationAddress($arrStation2);
        $chnStation2 = $ticketChnStation;
      }

      // Ticket info
      $key = $ticketDepStation->id() . $ticketArrStation->id() . $ticket->getTrainNumber()
        . $ticket->getCoachClass()->id() . $ticket->getSeatType()->id();
      if (!isset($ticketsInfo[$key])) {
        $ticketsInfo[$key] = [
          'count' => 1,
          'text' => $this->generateTicketsInfo($ticket),
        ];
      }
      else {
        $ticketsInfo[$key]['count']++;
      }

      // Passenger info.
      /** @var \Drupal\train_base\Entity\Passenger $passenger */
      foreach ($ticket->getPassengers() as $passenger) {
        $passengerText = $passenger->getFirstName() . '=' . $passenger->getLastName();

        $dob = $passenger->getDob() ? $passenger->getDob()->format('Y-m-d') : '';
        $details = array_filter([$passenger->getGender(), $passenger->getIdNumber(), $dob, $passenger->getCitizenship()]);
        if (!empty($details)) {
          $passengerText .= ' (' . implode(', ', $details) . ')';
        }
        $travelers[] = $passengerText;

        if ($passenger->getCitizenship()) {
          $passengerCitizenships[] = $passenger->getCitizenship();
        }
        $pax++;
      }
    }


    $record->board_pass__c = $boarding_pass;
    if (!empty($boarding_pass_instructions)) {
      $record->Boarding_pass_instructions__c  = substr(implode(', ', $boarding_pass_instructions), 0, 255);
    }
    if ($start_date) {
      $record->Tour_start_date__c             = date('c', $start_date);
    }
    if ($end_date) {
      $record->Tour_end_date__c               = date('c', $end_date);
    }
    if (!empty($forwardCarNumbers)) {
      $record->Car__c                         = implode(',', $forwardCarNumbers);
    }
    if (!empty($returnCarNumbers)) {
      $record->Return_car__c                  = implode(',', $returnCarNumbers);
    }
    if ($depStation1) {
      $record->Departure_station__c           = $depStation1->getParentStation() ? $depStation1->getName() : 'Central station';
      $record->Address__c                     = $depAddress1;
    }
    if ($depStation2) {
      $record->Departure_station_2__c         = $depStation2->getParentStation() ? $depStation2->getName() : 'Central station';
      $record->Address_2__c                   = $depAddress2;
    }
    if ($arrStation1) {
      $record->Arrival_station__c             = $arrStation1->getParentStation() ? $arrStation1->getName() : 'Central station';
      $record->Address_arrival__c             = $arrAddress1;
    }
    if ($arrStation2) {
      $record->Arrival_station_2__c           = $arrStation2->getParentStation() ? $arrStation2->getName() : 'Central station';
      $record->Address_arrival_2__c           = $arrAddress2;
    }
    if ($chnStation1) {
      $record->Change_station__c              = $chnStation1->getParentStation() ? $chnStation1->getParentStation()->getName() . ' (' . $chnStation1->getName() . ')' : $chnStation1->getName() . ' (Central station)';
    }
    if ($chnStation2) {
      $record->Change_station_2__c            = $chnStation2->getParentStation() ? $chnStation2->getParentStation()->getName() . ' (' . $chnStation2->getName() . ')'  : $chnStation2->getName() . ' (Central station)';
    }
    if (!empty($ticketsInfo)) {
      $record->RT_ticket_info__c              = '';
      foreach ($ticketsInfo as $item) {
        $record->RT_ticket_info__c            .= str_replace('%COUNT%', $item['count'], $item['text']);
      }
    }
    if (!empty($travelers)) {
      $record->Traveler_names__c              = implode("\n", $travelers);
      $record->Is_direct__c                   = false;
      $record->Nationality__c                 = implode(",", $passengerCitizenships);
    }
    else {
      $record->Is_direct__c                   = true;
    }
    $record->PAX__c                           = $pax;

    return $record;
  }


  /**
   * Adds services info to the opportunity.
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param \stdClass $record
   * @return \stdClass
   */
  protected function exportServicesInfo(StoreOrder $order, \stdClass $record) {
    $optionalServiceDetails = [];
    $seatPreferences = '';
    if ($orderItems = $order->getOrderItems()) {
      /** @var \Drupal\store\Entity\OrderItem $orderItem */
      foreach ($orderItems as $orderItem) {
        if ($orderItem->bundle() == 'optional_service') {
          if ($product = $orderItem->getProduct()) {
            $details = 'OPTIONAL SERVICE: ' . $product->getName() . "\n";
            if ($product->getFieldForm() == 'seat_preference_form') {
              $details .= 'seat preference: ';
              $seatPreferences = implode(', ', $orderItem->getData('pickedData'));
              $details .= $seatPreferences;
            }
            else {
              foreach ($orderItem->getData('pickedData') as $option => $value) {
                $details .= $option . ': ' . $value;
              }
            }
            $optionalServiceDetails[] = $details;
          }
        }
      }
    }

    $record->Details_received__c = implode("\n\n", $optionalServiceDetails);
    $record->Preferred_seats__c = $seatPreferences;

    return $record;
  }

  /**
   * Converts Opportunity stage to store order status.
   *
   * @param string $stage
   * @return bool|string
   */
  protected function salesforceToLocalOrderStatus($stage) {
    return array_search($stage, static::$statusStageMapping);
  }

  /**
   * Converts Opportunity stage to store order status.
   *
   * @param string $status
   * @return string
   */
  protected function localToSalesforceOrderStatus($status) {
    return isset(self::$statusStageMapping[$status]) ? self::$statusStageMapping[$status] : false;
  }

  /**
   * Generates the text for field RT_ticket_info__c.
   *
   * @param \Drupal\train_base\Entity\TrainTicket $ticket
   * @return string
   */
  protected function generateTicketsInfo(TrainTicket $ticket) {
    $services = [];
    /** @var \Drupal\train_base\Entity\CarService $carService */
    foreach ($ticket->getCarServices() as $carService) {
      $services[] = $carService->getName();
    }
    $depDateTime = $ticket->getDepartureDateTime();
    $depDateTime->setTimezone($ticket->getDepartureStation()->getTimezone());
    $depCity = $ticket->getDepartureStation()->getParentStation() ? : $ticket->getDepartureStation();
    $arrCity = $ticket->getArrivalStation()->getParentStation() ? : $ticket->getArrivalStation();
    /** @var \Drupal\store\Entity\StoreOrder $order */
    $order = $this->getEntity();
    $orderItems = $order->getOrderItems();
    /** @var \Drupal\store\Entity\OrderItem $orderItem */
    foreach ($orderItems as $orderItem) {
       if ($orderItem->bundle() == 'ticket' && $orderItem->getLegNumber() == $ticket->getLegNumber() && $orderItem->getProduct()) {
         $product = $orderItem->getProduct();
         $product_description = $product->getDescription();
         break;
       }
    }

    $ticketText = [];
    $ticketText['train'] = 'Train #' . $ticket->getTrainNumber() . ' '
      . $depCity . ' (' . $ticket->getDepartureStation()->getAddress()->getCountryCode() . ')';
    if ($ticket->getChangeStation()) {
      $chnCity = $ticket->getChangeStation()->getParentStation() ? : $ticket->getChangeStation();
      $ticketText['train'] .= '--' . $chnCity . ' (' . $ticket->getChangeStation()->getAddress()->getCountryCode() . ')';
    }
    $ticketText['train'] .= '--' . $arrCity . ' (' . $ticket->getArrivalStation()->getAddress()->getCountryCode() . ').';

    $ticketText['datetime'] = 'Departure date/time: ' . $depDateTime->format('D, j M Y, H:i') . '.';

    $ticketText['ticket'] = 'Ticket class: ' . $ticket->getCoachClass() . '. '
      . $ticket->getSeatType() . '.';
    if (!empty($services)) {
      $ticketText['ticket'] .= ' ' . implode(', ', $services) . '.';
    }
    $ticketText['count'] = 'Number of tickets %COUNT%.';
    if (!empty($product_description)) {
      $ticketText['product_description'] = $product_description;
    }

    return implode("\n", $ticketText) . "\n";
  }

  /**
   * Creates a new Order2 record in Salesforce.
   *
   * @param \stdClass $opportunity
   */
  protected function createOrder2(\stdClass $opportunity) {
    $masterOrder2 = new \stdClass();
    $masterOrder2->RecordTypeId = array_search(static::ORDER2_RAIL_TICKET_RECORD_TYPE, $this->getRecordTypes('Order2__c'));
    $masterOrder2->Opportunity__c = $opportunity->Id;
    $masterOrder2->Logist__c = $this->getOrder2LogistId();

    $prevDepStation = null;
    $orders = $ticketsInfo = [];
    /** @var \Drupal\store\Entity\StoreOrder $storeOrder */
    $storeOrder = $this->getEntity();
    $orderItems = $storeOrder->getOrderItems();
    /** @var \Drupal\train_base\Entity\TrainTicket $ticket */
    foreach ($storeOrder->getTickets() as $ticket) {
      $leg = $ticket->getLegNumber();
      $depStation = $ticket->getDepartureStation();
      $arrStation = $ticket->getArrivalStation();
      $coachClass = $ticket->getCoachClass();
      $supplier = $coachClass->getSupplier();

      // Ticket info
      if (!isset($ticketsInfo[$leg])) {
        $ticketsInfo[$leg] = [
          'count' => 1,
          'text' => $this->generateTicketsInfo($ticket),
        ];
      }
      else {
        $ticketsInfo[$leg]['count']++;
      }

      if (!isset($orders[$leg])) {
        $order2 = clone $masterOrder2;
        $order2->Start_date__c = date('c', $ticket->getDepartureDateTime()->getTimestamp());
        $order2->End_date__c = date('c', $ticket->getDepartureDateTime()->getTimestamp());
        $order2->Location__c = (string) ($depStation->getParentStation() ? $depStation->getParentStation() : $depStation);
        $order2->Destination__c = (string) ($arrStation->getParentStation() ? $arrStation->getParentStation() : $arrStation);
        $order2->Time__c = $ticket->getDepartureDateTime()->format('H:i');
        $order2->Type__c = $ticket->isBoardingPassRequired() ? static::ORDER2_BOARDING_PASS_TICKET_TYPE : static::ORDER2_E_TICKET_TICKET_TYPE;
        $order2->Travel_class__c = $coachClass->getName();

        // Add travelers info.
        $travelersInfo = $this->getTravelersInfo($leg);
        $order2->Travelers__c = $travelersInfo['travelers_data'];
        $order2->PAX__c = $travelersInfo['pax'];
        $currency = $supplier->getCurrency();

        /** @var \Drupal\store\Entity\OrderItem $orderItem */
        foreach ($orderItems as $orderItem) {
          if ($orderItem->bundle() == 'ticket' && $orderItem->getLegNumber() == $leg) {
            if ($originalPrice = $orderItem->getOriginalPrice()) {
              if (!empty($currency)) {
                $originalPrice = $originalPrice->convert($currency);
              }
              $order2->Cost__c = $originalPrice->getNumber() * $orderItem->getQuantity();
              $order2->CurrencyIsoCode = $originalPrice->getCurrencyCode();
              break;
            }
          }
        }

        // Add account for each order2.
        $email = $supplier->getEmail();
        $order2->Account__c = $this->getOrder2AccountId($email);

        // Create a payable invoice.
        if ($supplier->getCreatePayableInvoice()) {
          /** @var \Drupal\store\Entity\StoreOrder $order */
          $order = $this->getEntity();
          if (!$order->getData('Payable Invoice exported')) {
            try {
              $payableInvoice = $this->createPayableInvoice($order2);
              $order2->Invoice__c = $payableInvoice->Id;
              $order->setData('Payable Invoice exported', true)->save();
            }
            catch (\Exception $exception) {
              // Since oppty already created but it's id is not on mapping object we
              // try to suppress any exceptions to avoid from leaving oppty not mapped.
              watchdog_exception('salesforce', $exception);
            }
          }
        }
        elseif ($supplier->getRunningBalanceId()) {
          $order2->Running_balance__c = $supplier->getRunningBalanceId();
        }

        $orders[$leg] = $order2;
      }
    }

    foreach ($orders as $leg => $order2) {
      $order2->Order_details__c = str_replace('%COUNT%', $ticketsInfo[$leg]['count'], $ticketsInfo[$leg]['text']);
      $this->salesforceApi->createRecord('Order2__c', $order2);
    }
  }

/**
 * Creates a new Payable Invoice record in Salesforce.
 * @param $order2
 * @return \stdClass
 * @throws \Drupal\salesforce\SalesforceException
 */
  protected function createPayableInvoice($order2) {
    /** @var \Drupal\store\Entity\StoreOrder $order */
    $order = $this->getEntity();
    $invoice = reset($order->getInvoices());

    $userMappingObject = $this->assureExport($invoice->getUser());
    if (!$userMappingObject || !$userMappingObject->getRecordId()) {
      throw new SalesforceException('Invoice owner has not been exported yet.');
    }

    $payableInvoice = new \stdClass();
    $payableInvoice->Account__c = $order2->Account__c;
    $payableInvoice->Due_date__c = DrupalDateTime::createFromTimestamp(time())->format('c');
    $payableInvoice->Opportunity__c = \Drupal::service('config.factory')->get('store.settings')->get('opportunity_id_for_payable_invoices');
    $payableInvoice->Site__c = Master::siteCode();
    $payableInvoice->RecordTypeId = array_search(InvoiceSalesforceMapping::INVOICE_RECORD_TYPE, $this->getRecordTypes('Invoice__c'));
    $payableInvoice->Invoice_type__c = InvoiceSalesforceMapping::PAYABLE_INVOICE_TYPE;
    $payableInvoice->Status__c = 'UNPAID';
    $payableInvoice->CurrencyIsoCode = $order2->CurrencyIsoCode;
    $payableInvoice->Amount__c = $order2->Cost__c;
    $payableInvoice->Payment_by__c = 'Credit card';
    $payableInvoice->Autogenerated__c = true;

    $id = $this->salesforceApi->createRecord('Invoice__c', $payableInvoice);

    $payableInvoice->Id = $id;
    if (!$payableInvoice->Id) {
      throw new SalesforceException('Record hasn\'t been created.');
    }

    return $payableInvoice;
  }

  /**
   * Gets travelers info, collected from all ticket with the leg number.
   *
   * @param $leg
   * @return array
   */
  protected function getTravelersInfo($leg) {
    /** @var \Drupal\store\Entity\StoreOrder $storeOrder */
    $storeOrder = $this->getEntity();
    $pax = 0;
    $travelersData = '';
    /** @var \Drupal\train_base\Entity\TrainTicket $ticket */
    foreach ($storeOrder->getTickets() as $ticket) {
      if ($ticket->getLegNumber() == $leg) {
        /** @var \Drupal\train_base\Entity\Passenger $passenger */
        foreach ($ticket->getPassengers() as $passenger) {
          $pax++;
          $travelerData = [];
          if ($passenger->getName()) {
            $countryList = \Drupal::service('country_manager')->getList();
            /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $country */
            $country = isset($countryList[$passenger->getCitizenship()]) ? $countryList[$passenger->getCitizenship()] : null;
            $travelerData[] = $passenger->getName();
            $travelerData[] = $passenger->getDob() ? $passenger->getDob()->format('j/n/Y') : null;
            $travelerData[] = $country ? $country->getUntranslatedString() : null;
            $travelerData[] = $passenger->getIdNumber();
            $travelerData[] = $passenger->getGender() ? $passenger->getGender() == 'male' ? 'M' : 'F' : null;
            $travelersData .= implode(', ', array_filter($travelerData)) . "\n";
          }
        }
      }
    }

    return ['pax' => $pax, 'travelers_data' => $travelersData];
  }

  /**
   * Gets the default logist assigned to Order2.
   *
   * @return string
   */
  protected function getOrder2LogistId() {
    $id = $this->cacheBackend->get('order2_default_logist_id');
    if (!$id) {
      $query = new SelectQuery('Logist__c');
      $query->condition('Email__c', "'" . static::ORDER2_LOGIST_EMAIL . "'")
        ->field('Id');
      $logists = $this->salesforceApi->query($query);
      $logist = reset($logists);
      if ($logist) {
        $id = $logist->Id;
        $this->cacheBackend->set('order2_default_logist_id', $id);
      }
    }
    else {
      $id = $id->data;
    }

    return $id;
  }

  /**
   * Gets the default account the Order2 is assigned to.
   *
   * @param $email
   * @return string
   */
  protected function getOrder2AccountId($email = null) {
    $email = $email ? : static::ORDER2_ACCOUNT_EMAIL;
    $cache = $this->cacheBackend->get('order2_default_account_id');
    $cacheData = $cache ? $cache->data : [];
    $id = null;
    if (empty($cacheData[$email])) {
      $query = new SelectQuery('Account');
      $query->condition('Custom_email__c', "'" . $email . "'")
        ->field('Id');
      $accounts = $this->salesforceApi->query($query);
      $account = reset($accounts);
      if ($account) {
        $id = $account->Id;
        $cacheData[$email] = $id;
        $this->cacheBackend->set('order2_default_account_id', $cacheData);
      }
    }
    else {
      $id = $cacheData[$email];
    }

    return $id;
  }

  /**
   * Gets station's address.
   *
   * @param Station $station
   * @return null|string
   */
  protected function getStationAddress($station) {
    $address = $station->getAddress();
    if ($address->getCountryCode()) {
      $addressParts = [
        $address->getAddressLine1(),
        $address->getLocality(),
        \Drupal::service('address.country_repository')->get($address->getCountryCode())
      ];
      return implode(', ', array_filter($addressParts));
    }
    else {
      \Drupal::logger('salesforce')->critical('Address is not set.', [
        'link' => $station->toLink('view')
          ->toString()
      ]);

      return null;
    }
  }

}
