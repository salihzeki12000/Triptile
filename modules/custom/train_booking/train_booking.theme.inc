<?php

use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

/**
 * Implements template_preprocess_hook
 */
function template_preprocess_coach_class_info(&$vars) {
  /** @var \Drupal\train_provider\CoachClassInfoHolder $coachClassInfoHolder */
  $coachClassInfoHolder = $vars['data'];
  $vars['data'] = [];
  $coach_class = $coachClassInfoHolder->getCoachClass();
  $count = $coachClassInfoHolder->getCountOfAvailableTickets();
  // @todo make it configurable.
  if (isset($count) && $count > 0 && $count < 20) {
    $vars['data']['count_of_available_tickets'] = \Drupal::translation()->formatPlural($count, '1 seat left', '@count seats left');
  }
  $vars['data']['name'] = $coach_class->getName();
  $vars['data']['price'] = $coachClassInfoHolder->getPrice()->__toString();
  $vars['data']['currency_code'] = $coachClassInfoHolder->getPrice()->getCurrencyCode();
  /** @var \Drupal\train_base\Entity\CarService $carService */
  foreach ($coachClassInfoHolder->getCarServices() as $carService) {
    if ($carService->getImage()) {
      $vars['data']['car_services'][]['image'] = [
        '#theme' => 'image',
        '#uri' => $carService->getImage()->getFileUri(),
        '#alt' => $carService->getName(),
        '#title' => $carService->getName(),
      ];
    }
  }

  $url = Url::fromRoute(
    'train_booking.coach_class_gallery',
    array(
      'coach_class_id' => $coach_class->id()
    ),
    array());

  $vars['data']['name'] = $coach_class->getName();
  $gallery = $coach_class->getGallery();

  if(!empty($gallery)) {
    $vars['data']['gallery'] = [
      '#title' => '',
      '#type' => 'link',
      '#url' => $url,
      '#options' => [
        'attributes' => [
          'class' => [
            'use-ajax',
            'gallery-link'
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ]
      ],
      '#attached' => ['library' => [
        'core/drupal.dialog.ajax',
        'train_booking/close-modal-dialog'
      ]],
    ];
  }

}

/**
 * Implements template_preprocess_hook.
 */
function template_preprocess_route_leg_info(&$vars) {
  /** @var \Drupal\train_provider\TrainInfoHolder $trainInfoHolder */
  $trainInfoHolder = $vars['train_info'];
  /** @var \Drupal\train_provider\CoachClassInfoHolder $coachClassInfoHolder */
  $coachClassInfoHolder = $vars['coach_class_info'];
  $vars['coach_class'] = $coachClassInfoHolder->getCoachClass();
  $vars['coach_class_id'] = $coachClassInfoHolder->getCoachClass()->id();
  $vars['departure_station_city'] = $vars['departure_station']->getParentStation() ? : $vars['departure_station'];
  $vars['arrival_station_city'] = $vars['arrival_station']->getParentStation() ? : $vars['arrival_station'];
  $vars['train_number'] = $trainInfoHolder->getTrainNumber();
  $vars['train_name'] = $trainInfoHolder->getTrainName();
  $vars['train_class'] = $trainInfoHolder->getTrainClass();
  $vars['departure_time_zone'] = $vars['departure_station']->getCountryCode() == 'RU' ? t('Moscow time') : t('Local time');
  $vars['arrival_time_zone'] = $vars['arrival_station']->getCountryCode() == 'RU' ? t('Moscow time') : t('Local time');
  if (!$vars['departure_station']->getParentStation()) {
    $vars['departure_station'] = t('Central station', [], ['context' => 'Route leg info']);
  }
  if (!$vars['arrival_station']->getParentStation()) {
    $vars['arrival_station'] = t('Central station', [], ['context' => 'Route leg info']);
  }
  $vars = array_merge($vars,\Drupal::service('train_booking.render_helper')->getFullDepartureArrivalDates($vars['departure_datetime'], $vars['arrival_datetime']));

  // Additional data for GA.
  /** @var \Drupal\store\Price $profit */
  $profit = $coachClassInfoHolder->getPrice()->subtract($coachClassInfoHolder->getOriginalPrice());
  $vars['price'] = $coachClassInfoHolder->getPrice()->getNumber();
  $vars['profit'] = $profit->getNumber();
  $vars['currency_code'] = $profit->getCurrencyCode();
}

/**
 * Implements template_preprocess_hook.
 */
function template_preprocess_order_details(&$vars) {
  /** @var \Drupal\store\Entity\StoreOrder $order */
  $order = $vars['order'];

  /** @var \Drupal\train_provider\CoachClassInfoHolder $coachClass */
  $coachClasses = $vars['coach_class_info'];
  $allOrderItems = $vars['order_items'];
  $vars['total_price'] = $order->getOrderTotal();
  foreach ($allOrderItems as $orderItemType => $orderItems) {
    if ($orderItemType == 'order_tax') {
      // Expects that tax is alone on the whole order. But we have to store it as an element of array
      // for more clear storing.
      /** @var \Drupal\store\Entity\OrderItem $tax */
      $tax = $orderItems[0];
      $vars['tax']['price'] = $tax->getPrice();
      $vars['tax']['name'] = t('Taxes', [], ['context' => 'Sidebar Order Details']);
    }
    elseif ($orderItemType == 'optional_service') {
      /** @var \Drupal\store\Entity\OrderItem $orderItem */
      foreach ($orderItems as $orderItem) {
        if ($orderItem->getProduct() && $orderItem->getProduct()->getFieldForm() == 'seat_preference_form') {
          $seatPreferences = \Drupal::service('entity_type.manager')->getStorage('seat_preference')->loadMultiple(array_keys($orderItem->getData('pickedData')));
          /** @var \Drupal\train_base\Entity\SeatPreference $seatPreference */
          foreach ($seatPreferences as $seatPreference) {
            $vars['optional_service'][] = [
              'price' => $orderItem->getPrice()->multiply($orderItem->getQuantity()),
              'name' => $seatPreference->getName(),
            ];
          }
        }
        else {
          $vars['optional_service'][] = [
            'price' => $orderItem->getPrice()->multiply($orderItem->getQuantity()),
            'name' => $orderItem->getName(),
          ];
        }
      }
    }
    elseif ($orderItemType == 'delivery_service') {
      /** @var \Drupal\store\Entity\OrderItem $orderItem */
      foreach ($orderItems as $orderItem) {
        $vars['delivery_service'][] = [
          'price' => $orderItem->getPrice()->multiply($orderItem->getQuantity()),
          'name' => $orderItem->getName(),
        ];
      }
    }
    else {
      $leg = $orderItemType;
      if($vars['complex_trip']) {
        $route_name = $leg == '1' ? t('First leg', [], ['context' => 'Sidebar Order Details'])
          : t('Second leg', [], ['context' => 'Sidebar Order Details']);
        if ($vars['round_trip']) {
          $route_name = $leg == '1' ? t('Outbound trip', [], ['context' => 'Sidebar Order Details'])
            : t('Inbound trip', [], ['context' => 'Sidebar Order Details']);
        }
      }
      else {
        $route_name = '';
      }
  
      if(isset($coachClasses[$leg])) {
        /** @var \Drupal\train_provider\CoachClassInfoHolder $coachClassInfoHolder */
        $coachClassInfoHolder = $coachClasses[$leg]['coach_class_info'];
        $coachClass = $coachClassInfoHolder->getCoachClass();
        $vars['routes_data'][$leg]['coach_class'] = $coachClass;
      }
      $tickets_number = 0;
      $vars['routes_data'][$leg]['route_name'] = $route_name;
      /** @var \Drupal\store\Entity\OrderItem $orderItem */
      foreach ($orderItems as $orderItem) {
        $item['quantity'] = $orderItem->getQuantity();
        $item['price'] = $orderItem->getPrice()->multiply($orderItem->getQuantity());
        $tickets_number += $item['quantity'];
        // Use English version
        switch ($orderItem->get('name')->value) {
          case 'Adult':
            $item['name'] = \Drupal::translation()->formatPlural($orderItem->getQuantity(), '1 adult ticket', '@count adult tickets', [], ['context' => 'Sidebar Order Details']);
            break;

          case 'Child':
            $item['name'] = \Drupal::translation()->formatPlural($orderItem->getQuantity(), '1 child ticket', '@count child tickets', [], ['context' => 'Sidebar Order Details']);
            break;

          case 'Infant':
            $item['name'] = \Drupal::translation()->formatPlural($orderItem->getQuantity(), '1 infant ticket', '@count infant tickets', [], ['context' => 'Sidebar Order Details']);
            break;

          default:
            $item['name'] = $orderItem->getName();
        }
        $vars['routes_data'][$leg]['tickets_number'] = $tickets_number;
        $vars['routes_data'][$leg]['order_items'][] = $item;
      }
    }
  }

  // Free services must be higher then paid services.
  if (!empty($vars['optional_service'])) {
    sort_optional_services_by_price($vars['optional_service']);
  }
}

/**
 * Implements template_preprocess_hook.
 */
function template_preprocess_passengers_info(&$vars) {
  $passengers = [];
  $count = 0;
  foreach ($vars['passengers'] as $passenger) {
    $count++;
    $passenger['number'] = $count;
    // @todo add formatter to passenger title.
    if (!empty($passenger['gender'])) {
      $passenger['gender'] = \Drupal::service('train_booking.render_helper')->getGenderFirstLetter($passenger['gender']);
    }
    $passenger['dob'] = !empty($passenger['dob']) ? $passenger['dob']->format('M j, Y') : '';
    $passengers[] = $passenger;
  }
  $vars['passengers'] = $passengers;
}

/**
 * Sort optional services by its price.
 *
 * @param array $services
 */
function sort_optional_services_by_price(&$services) {
  usort($services, 'cmp_sort_services');
}

/**
 * @param array $service1
 * @param array $service2
 * @return int
 */
function cmp_sort_services($service1, $service2) {
  $service1PriceNumber = $service1['price']->getNumber();
  $service2PriceNumber = $service2['price']->getNumber();
  if ($service1PriceNumber == $service2PriceNumber) {
    return 0;
  }
  else if ($service1PriceNumber < $service2PriceNumber) {
    return -1;
  }
  else {
    return 1;
  }
}