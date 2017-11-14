<?php

namespace Drupal\train_booking\Plugin\OrderRenderer;

use Drupal\Core\Url;
use Drupal\store\Entity\StoreOrder;
use Drupal\store\Annotation\OrderRenderer;
use Drupal\store\Plugin\OrderRenderer\OrderRendererBase;
use Drupal\store\Price;
use Drupal\train_base\Entity\TrainTicket;
use Drupal\train_booking\RenderHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides Train Order Renderer
 *
 * @OrderRenderer(
 *   id = "train_order_renderer",
 *   label = "Train order renderer",
 *   order_type = "train_order"
 * )
 */

class TrainOrderRenderer extends OrderRendererBase implements ContainerFactoryPluginInterface{

  const THANK_YOU_PAGE_TYPE = 'thank_you_page',
        ORDER_PAGE_TYPE = 'order_page',
        THANK_YOU_PAGE_CONTEXT = 'Thank You Page',
        ORDER_PAGE_CONTEXT = 'Order ticket',
        THANK_YOU_PAGE_DATETIME_FORMAT = 'H:i, M j, Y (D)';

  protected $renderHelper;

  /**
   * Constructs a TrainOrderRenderer object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\train_booking\RenderHelper $renderHelper
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RenderHelper $renderHelper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderHelper = $renderHelper;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('train_booking.render_helper')
    );
  }

  /**
   * Gets thank you page renderable array
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @return array
   */
  public function getThankYouPage(StoreOrder $order) {
    return [
      '#theme' => 'thank_you_page',
      '#welcome_text' => $this->getUserText($order),
      '#search_button' => $this->getSearchButton(t('Continue searching', [], ['context' => static::THANK_YOU_PAGE_CONTEXT])),
      '#order_info' => $this->getStoreOrderInfo($order, static::THANK_YOU_PAGE_TYPE),
      '#trains' => $this->getTrainsInfo($order, static::THANK_YOU_PAGE_TYPE),
      '#cache' => [
        'tags' => $this->getPageCacheTags($order, static::THANK_YOU_PAGE_TYPE),
        'contexts' => ['url.path']
      ],
      '#attached' => [
        'library' => [
          'train_booking/thank-you-page'
        ],
      ],
    ];
  }

  /**
   * Gets order page renderable array
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @return array
   */
  public function getOrderPage(StoreOrder $order) {
    return [
      '#theme' => 'train_order_page',
      '#store_order' => $this->getStoreOrderInfo($order, static::ORDER_PAGE_TYPE),
      '#trains' => $this->getTrainsInfo($order, static::ORDER_PAGE_TYPE),
      '#search_button' => $this->getSearchButton(t('Search trains', [], ['context' => static::ORDER_PAGE_CONTEXT])),
      '#pdf_files' => $this->getOrderPDF($order),
      '#cache' => [
        'tags' => $this->getPageCacheTags($order, static::ORDER_PAGE_TYPE),
        'contexts' => ['url.path']
      ],
      '#attached' => [
        'library' => [
          'train_booking/tickets-download',
        ],
        'drupalSettings' => [
          'orderHash' => $order->getHash(),
        ],
      ],
    ];
  }

  /**
   * Gets cache tags for page
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $page_type
   * @return array|\string[]
   */
  protected function getPageCacheTags(StoreOrder $order, $page_type) {
    $cacheTags = $order->getCacheTags();
    /** @var \Drupal\train_base\Entity\TrainTicket $ticket */
    foreach ($order->getTickets() as $ticket) {
      $cacheTags = array_merge($cacheTags, array_diff($ticket->getCacheTags(), $cacheTags));

      /** @var \Drupal\train_base\Entity\Station $departureStation */
      if ($departureStation = $ticket->getDepartureStation()) {
        $cacheTags = array_merge($cacheTags, array_diff($departureStation->getCacheTags(), $cacheTags));
      }
      /** @var \Drupal\train_base\Entity\Station $arrivalStation */
      if ($arrivalStation = $ticket->getArrivalStation()) {
        $cacheTags = array_merge($cacheTags, array_diff($arrivalStation->getCacheTags(), $cacheTags));
      }
      /** @var \Drupal\train_base\Entity\TrainClass $trainClass */
      if ($trainClass = $ticket->getTrainClass()) {
        $cacheTags = array_merge($cacheTags, array_diff($trainClass->getCacheTags(), $cacheTags));
      }
      /** @var \Drupal\train_base\Entity\CoachClass $coachClass */
      if ($coachClass = $ticket->getCoachClass()) {
        $cacheTags = array_merge($cacheTags, array_diff($coachClass->getCacheTags(), $cacheTags));
      }
      /** @var \Drupal\train_base\Entity\SeatType $seatType */
      if ($seatType = $ticket->getSeatType()) {
        $cacheTags = array_merge($cacheTags, array_diff($seatType->getCacheTags(), $cacheTags));
      }
      /** @var \Drupal\train_base\Entity\Passenger $passenger */
      foreach ($ticket->getPassengers() as $passenger) {
        $cacheTags = array_merge($cacheTags, array_diff($passenger->getCacheTags(), $cacheTags));
      }

      switch ($page_type) {
        case static::THANK_YOU_PAGE_TYPE:
          /** @var \Drupal\store\Entity\Invoice $invoice */
          if ($invoice = $this->getFirstInvoice($order)) {
            $cacheTags = array_merge($cacheTags, array_diff($invoice->getCacheTags(),$cacheTags));
          }
          /** @var \Drupal\rn_user\Entity\User $user */
          if ($user = $order->getOwner()) {
            $cacheTags = array_merge($cacheTags, array_diff($user->getCacheTags(), $cacheTags));
          }
          break;
        case static::ORDER_PAGE_TYPE:
          break;
      }
    }
    return $cacheTags;
  }

  /**
   * Gets first invoice attached to order
   *
   * @param $order
   * @return mixed|null
   */
  protected function getFirstInvoice(StoreOrder $order) {
    $invoices = $order->getInvoices();
    return is_array($invoices) ? reset($invoices) : null;
  }

  /**
   * Gets info from store order
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $page_type
   * @return array
   */
  protected function getStoreOrderInfo(StoreOrder $order, $page_type) {
    $store_order_info = [
      'number' => $order->getOrderNumber(),
      'total'  => $order->getOrderTotal(),
    ];
    switch ($page_type) {
      case static::THANK_YOU_PAGE_TYPE:
        if ($invoice = $this->getFirstInvoice($order)) {
          $customerProfile = $invoice ? $invoice->getCustomerProfile() : NULL;
          $store_order_info['customer_country_code'] = $customerProfile ? $customerProfile->getAddress()->getCountryCode() : '';
        }
        $store_order_info = array_merge($store_order_info, $this->getStoreOrderGAInfo($order));
        break;
      case static::ORDER_PAGE_TYPE:
        $store_order_info = array_merge($store_order_info, [
          'status' => $order::getStateName($order->getState()),
          'status_description' => $order->getStateDescription(),
          'status_class' => 'state-' . $order->getState(),
        ]);
        break;
    }
    return $store_order_info;
  }

  /**
   * Gets text for order owner
   *
   * @param $order
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */

  protected function getUserText(StoreOrder $order) {
    /** @var \Drupal\rn_user\Entity\User $user */
    if ($user = $order->getOwner()) {
      if (!$order->getTicketIssueDate()) {
        return t('Dear @account_name,<br/>We received your order <strong>#@order_number</strong>. Your tickets will be issued within 1 business day and sent to your email <a href="mailto:@email">@email</a>.', ['@email' => $user->getEmail(), '@account_name' => $user->getFullName(), '@order_number' => $order->getOrderNumber()], ['context' => static::THANK_YOU_PAGE_CONTEXT]);
      }
      else {
        return t('Dear @account_name,<br/>We received your order <strong>#@order_number</strong>. Your tickets will be issued about @ticket_issue_date and sent to your email <a href="mailto:@email">@email</a>.', ['@email' => $user->getEmail(), '@ticket_issue_date' => $order->getTicketIssueDate()->format(static::THANK_YOU_PAGE_DATETIME_FORMAT), '@account_name' => $user->getFullName(), '@order_number' => $order->getOrderNumber()], ['context' => static::THANK_YOU_PAGE_CONTEXT]);
      }
    }
  }

  /**
   * Gets link to homepage
   *
   * @param $title
   * @return array
   */
  protected function getSearchButton($title) {
    return [
      'link' => [
        '#title' => $title,
        '#type' => 'link',
        '#url' => new Url('<front>'),
        '#attributes' => [
          'class' => [
            'home-link'
          ]
        ]
      ]
    ];
  }

  /**
   * Gets original order total
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @return \Drupal\store\Price
   */
  protected function getOriginalOrderTotal(StoreOrder $order) {
    $originalOrderTotal = \Drupal::service('store.price')->get(0, $order->getOrderTotal()->getCurrencyCode());
    /** @var \Drupal\store\Entity\OrderItem $orderItem */
    foreach ($order->getOrderItems() as $orderItem) {
      // @TODO: Also need calculate optional and delivery services profit.
      if ($orderItem->bundle() == 'ticket') {
        if ($originalPrice = $orderItem->getOriginalPrice()) {
          $originalPrices = $originalPrice->multiply($orderItem->getQuantity());
          $originalOrderTotal = $originalOrderTotal->add($originalPrices);
        }
      }
    }
    return $originalOrderTotal;
  }

  /**
   * Gets array of tickets from order grouped by train
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @return array
   */
  protected function getGroupedOrderTickets(StoreOrder $order) {
    $train_tickets = [];
    /** @var \Drupal\train_base\Entity\TrainTicket $ticket */
    foreach ($order->getTickets() as $ticket) {
      $train_tickets[$ticket->getTrainNumber()][$ticket->id()] = $ticket;
    }
    return $train_tickets;
  }

  /**
   * Gets trains data
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $page_type
   * @return array
   */
  protected function getTrainsInfo(StoreOrder $order, $page_type) {
    $tickets = [];
    foreach ($this->getGroupedOrderTickets($order) as $trainNumber => $trainTickets) {
      /** @var \Drupal\train_base\Entity\TrainTicket $ticket */
      if ($ticket = reset($trainTickets)) {
        // Train data
        $tickets[$trainNumber]['train_number'] = !empty($trainNumber) ? $trainNumber : '';
        $trainName = $ticket->getTrainName();
        $tickets[$trainNumber]['train_name'] = !empty($trainName) ? $trainName : '';

        // Departure station data
        $departureStation = $ticket->getDepartureStation();
        $tickets[$trainNumber]['departure_station'] = !empty($departureStation) ? $departureStation->getName() : '';
        $departureStationCity = $ticket->getDepartureCity();
        $tickets[$trainNumber]['departure_station_city'] = !empty($departureStationCity) ? $departureStationCity->getName() : '';

        // Arrival station data
        $arrivalStation = $ticket->getArrivalStation();
        $tickets[$trainNumber]['arrival_station'] = !empty($arrivalStation) ? $arrivalStation->getName() : '';
        $arrivalStationCity = $ticket->getArrivalCity();
        $tickets[$trainNumber]['arrival_station_city'] = !empty($arrivalStationCity) ? $arrivalStationCity->getName() : '';

        // Train class data
        $trainClass = $ticket->getTrainClass();
        $tickets[$trainNumber]['train_class'] = !empty($trainClass) ? $trainClass->getName() : '';

        // Coach class data
        $coachClass = $ticket->getCoachClass();
        $tickets[$trainNumber]['coach_class'] = !empty($coachClass) ? $coachClass->getName() : '';
        $tickets[$trainNumber]['coach_class_id'] = !empty($coachClass) ? $coachClass->id() : '';

        // Seat type data
        $seatType = $ticket->getSeatType();
        $tickets[$trainNumber]['seat_type'] = !empty($seatType) ? $seatType->getName() : '';

        $tickets[$trainNumber]['tickets_count'] = count($trainTickets);

        switch ($page_type) {
          case static::THANK_YOU_PAGE_TYPE:
            // Passengers data
            $tickets[$trainNumber]['passengers'] = $this->getTrainPassengersInfo($trainTickets);
            // Travel dates
            if ($departureDateTime = $ticket->getDepartureDateTime()) {
              $tickets[$trainNumber]['departure_date_time'] = $departureDateTime->format(static::THANK_YOU_PAGE_DATETIME_FORMAT);
            }
            if ($arrivalDateTime = $ticket->getArrivalDateTime()) {
              $tickets[$trainNumber]['arrival_date_time'] = $arrivalDateTime->format(static::THANK_YOU_PAGE_DATETIME_FORMAT);
            }
            // Google analytics
            $tickets[$trainNumber] = array_merge($tickets[$trainNumber], $this->getTrainGAInfo($order,$ticket));
            break;
          case static::ORDER_PAGE_TYPE:
            // Travel dates
            $departureDateTime = $ticket->getDepartureDateTime();
            $arrivalDateTime = $ticket->getArrivalDateTime();
            if ($departureDateTime && $arrivalDateTime) {
              $tickets[$trainNumber] = array_merge($tickets[$trainNumber], $this->renderHelper->getFullDepartureArrivalDates($departureDateTime, $arrivalDateTime));
            }
            // Passengers data
            $tickets[$trainNumber]['passengers'] = $this->getTrainPassengersInfo($trainTickets, true);
            break;
        }
      }
    }
    return $tickets;
  }

  /**
   * Gets passengers info from tickets
   *
   * @param $trainTickets
   * @param bool $showFullInfo
   * @return array
   */
  protected function getTrainPassengersInfo($trainTickets, $showFullInfo = false) {
    $passengers = [];
    /** @var \Drupal\train_base\Entity\TrainTicket $trainTicket */
    foreach ($trainTickets as $trainTicket) {
      /** @var \Drupal\train_base\Entity\Passenger $passenger */
      foreach ($trainTicket->getPassengers() as $key => $passenger) {
        $name = $passenger->getName();
        $passengers[]['name'] = !empty($name) ? $name : t('Name is not provided');

        if ($showFullInfo) {
          $passengers[$key]['title'] = $this->renderHelper->getTitleText($passenger->getTitle());
          if ($dob = $passenger->getDob()) {
            $passengers[$key]['dob'] = $dob->format('d.m.Y');
          }
          $passengers[$key]['citizenship'] = $passenger->getCitizenship();
          $passengers[$key]['passport'] = $passenger->getIdNumber();
          if ($passenger->getGender()) {
            $passengers[$key]['gender'] = $this->renderHelper->getGenderFirstLetter($passenger->getGender());
          }
        }
      }
    }
    return $passengers;
  }
  
  /**
   * Gets additional order info for google analytics
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @return array
   */
  protected function getStoreOrderGAInfo(StoreOrder $order) {
    $store_order_ga = [];
    $orderTotal = $order->getOrderTotal()->subtract($this->getOriginalOrderTotal($order));
    $store_order_ga['profit'] = $orderTotal->getNumber();
    $orderItems = $order->getOrderItems();
    /** @var \Drupal\store\Entity\OrderItem $orderItem */
    foreach ($orderItems as $orderItem) {
      if ($orderItem->bundle() == 'tax') {
        $store_order_ga['tax'] = [
          'name' => $orderItem->getName(),
          'profit' => $orderItem->getPrice()->getNumber(),
          'quantity' => $orderItem->getQuantity(),
        ];
      }
    }
    if (empty($store_order_ga['tax'])) {
      $store_order_ga['tax']['profit'] = 0;
    }
    return $store_order_ga;
  }

  /**
   * Gets additional train data for google analytics
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param \Drupal\train_base\Entity\TrainTicket $ticket
   * @return array
   */
  protected function getTrainGAInfo(StoreOrder $order, TrainTicket $ticket) {
    $trainGAInfo = [];
    $allOrderItems = $order->getOrderItems();
    /** @var \Drupal\store\Entity\OrderItem $orderItem */
    foreach ($allOrderItems as $orderItem) {
      // @TODO: Also need calculate optional and delivery services profit.
      if ($ticket->getLegNumber() === $orderItem->getLegNumber() && $orderItem->bundle() == 'ticket') {
        if ($originalPrice = $orderItem->getOriginalPrice()) {
          $currencyCode = $orderItem->getPrice()->getCurrencyCode();
          if (empty($trainGAInfo['coach_class_price'])) {
            /** @var \Drupal\store\Price $profit */
            $profit = $orderItem->getPrice()->subtract($originalPrice->convert($currencyCode));
            $trainGAInfo['coach_class_price'] = $orderItem->getPrice()->getNumber();
            $trainGAInfo['coach_class_profit'] = $profit->getNumber();
            $trainGAInfo['coach_class_currency_code'] = $currencyCode;
          }
        }
      }
    }
    return $trainGAInfo;
  }

  private function getOrderPDF(StoreOrder $order) {
    $pdf_files = [];
    /** @var \Drupal\file\Entity\File $file */
    foreach($order->getPdfFiles() as $key => $file) {
      $path = file_create_url($file->getFileUri());
      $url = Url::fromUri($path);
      $pdf_files[$key] = [
        'title' => ['#markup' => $file->getFilename()],
        'link' => [
          '#title' => t('THIS IS YOUR E-TICKET. Please download the PDF file and print it to board the train.'),
          '#type' => 'link',
          '#url' => $url,
          '#attributes' => ['class' => ['pdf-link', 'disabled', 'button']]
        ]
      ];
    }
    return $pdf_files;
  }
}
