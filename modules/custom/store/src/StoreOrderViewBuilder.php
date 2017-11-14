<?php

namespace Drupal\store;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Url;
use Drupal\Core\Link;

class StoreOrderViewBuilder extends  EntityViewBuilder {

  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);

    // @TODO: Move to twig template when html structure will be close to finalize version.
    if ($view_mode == 'full') {
      // Alter view build. Display additional booking information.
      /** @var \Drupal\store\Entity\StoreOrder $order */
      foreach ($entities as $entity_key => $order) {
        $bookingData = $order->getData('bookingData');
        if ($bookingData) {
          $output = '<div><b>BookingData: </b>';
          foreach ($bookingData as $leg => $data) {
            $output .= '<div><span>' . $data['route'] . ': </span><span>' . $data['providerId'] . '</span> ';
            if (isset($data['bookingKey'])) {
              $output .= print_r($data['bookingKey'], true) . ' ';
            }
            if (isset($data['status'])) {
              if ($data['status'] == 'booked') {
                $url = Url::fromRoute('train_booking.cancel_booking', ['order_id' => $order->id(), 'cancel_leg' => $leg]);
                $output .= Link::fromTextAndUrl($this->t('Cancel'), $url)->toString();
                if (isset($data['pdf'])) {
                  $url = Url::fromUri($data['pdf']);
                  $output .= ' | ' . Link::fromTextAndUrl($this->t('Download PDF'), $url)->toString();
                }
              }
              else if ($data['status'] == 'canceled') {
                $output .= $this->t('Canceled');
              }
            }
            if (isset($data['bookingKey'])) {
              $url = Url::fromRoute('train_booking.booking_info', ['order_id' => $order->id(), 'info_leg' => $leg]);
              $output .= ' | ' . Link::fromTextAndUrl($this->t('Get info'), $url)->toString();
            }
            if (!isset($data['bookingKey']) && isset($data['message'])) {
              $output .= ' | ' . $data['message'];
            }
            $output .= '</div>';
            if (!empty($data['ticketRef'])) {
              $output .= '<div>Tickets:</div>';
              foreach ($data['ticketRef'] as $ticket) {
                $output .= '<div>' . $ticket['booking-id'] . ' ';
                if (isset($ticket['status'])) {
                  if ($ticket['status'] == 'booked') {
                    $url = Url::fromRoute('train_booking.cancel_ticket_booking', ['order_id' => $order->id(), 'cancel_leg' => $leg,  'ticket_id' => $ticket['booking-id']]);
                    $output .= Link::fromTextAndUrl($this->t('Cancel'), $url)->toString();
                  }
                  else if ($ticket['status'] == 'canceled') {
                    $output .= $this->t('Canceled');
                  }
                }
                $output .= '</div>';
              }
            }
          }
          $output .= '</div>';
          $build[$entity_key]['booking_data'] = [
            '#markup' => $output,
            '#weight' => 15,
          ];
        }
      }
    }
  }

}