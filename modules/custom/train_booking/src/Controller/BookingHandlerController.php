<?php

namespace Drupal\train_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\train_booking\TrainBookingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BookingHandlerController extends ControllerBase {

  /**
   * @var \Drupal\train_booking\TrainBookingManager
   */
  protected $trainBookingManager;

  /**
   * BookingCancel constructor.
   * @param \Drupal\train_booking\TrainBookingManager $train_booking_manager
   */
  public function __construct(TrainBookingManager $train_booking_manager) {
    $this->trainBookingManager = $train_booking_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('train_booking.train_booking_manager')
    );
  }

  public function getInfo($order_id, $info_leg) {
    $this->trainBookingManager->bookingHandler('getInfo', $order_id, $info_leg);

    return new RedirectResponse(Url::fromRoute('entity.store_order.canonical', ['store_order' => $order_id])->toString());
  }

  public function cancelBooking($order_id, $cancel_leg) {
    $this->trainBookingManager->bookingHandler('cancelBooking', $order_id, $cancel_leg);

    return new RedirectResponse(Url::fromRoute('entity.store_order.canonical', ['store_order' => $order_id])->toString());
  }

  public function cancelTicketBooking($order_id, $cancel_leg, $ticket_id) {
    $this->trainBookingManager->bookingHandler('cancelTicketBooking', $order_id, $cancel_leg, $ticket_id);

    return new RedirectResponse(Url::fromRoute('entity.store_order.canonical', ['store_order' => $order_id])->toString());
  }

}