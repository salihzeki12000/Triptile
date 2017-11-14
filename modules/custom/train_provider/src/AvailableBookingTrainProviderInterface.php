<?php

namespace Drupal\train_provider;

/**
 * Defines an interface for train provider plugins, which allow do booking.
 */
interface AvailableBookingTrainProviderInterface {

  /**
   * Hold a Booking.
   *
   * @param array $legsResult
   * @param \Drupal\store\Entity\StoreOrder $order
   * @return array
   */
  public function preBooking($legsResult, $order);

  /**
   * Commit the current Booking.
   *
   * @param $legsResult
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param $preBookingResponse
   * @return array
   */
  public function finalizeBooking($legsResult, $order, $preBookingResponse);

  /**
   * Cancel the current Booking for specify leg.
   *
   * @param $bookingData
   * @param $order
   * @param $cancelLeg
   * @return array
   */
  public function cancelBooking($bookingData, $order, $cancelLeg);

  /**
   * Cancel the current Booking for specify leg.
   *
   * @param $bookingData
   * @param $order
   * @param $cancelLeg
   * @return array|void
   */
  public function checkPdf($bookingData, $order, $cancelLeg);

  /**
   * @param $bookingData
   * @param $order
   * @param $infoLeg
   * @return mixed
   */
  public function getInfo($bookingData, $order, $infoLeg);

}
