<?php

namespace Drupal\tt_config\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tt_config\Entity\TripOrder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SaveOrder extends ControllerBase {

  /*
   * Callback for save trip order.
   */
  public function save(Request $request) {
    $order = $request->getContent();
    $decode = json_decode($order);

    $when = $decode->common->whenGoDate;
    $who = $decode->common->whoCount;
    $array_for_hash = [$when, $who];
    foreach ($decode->steps as $step_index => $step) {
      foreach ($step as $entity_name => $entity) {
        if ($entity_name == 'activity') {
          foreach ($entity as $activity) {
            $array_for_hash[] = $activity->id;
          }
        }
        else {
          $array_for_hash[] = $entity->id;
        }
      }
    }
    $string_for_hash = implode('+', $array_for_hash);
    $hash = substr(md5($string_for_hash), 0, 19);

    $query = \Drupal::database()->select('trip_order', 't');
    $query->addField('t', 'id');
    $query->condition('t.hash', $hash);
    $query->range(0, 1);
    $id = $query->execute()->fetchField();

    if(!$id){
      $trip_order = TripOrder::create([
        'order_object' => [
          'value' => $order,
          'format' => 'NULL',
        ],
        'hash' => $hash,
      ]);
      $trip_order->save();
    }
    else{
      $trip_order = TripOrder::load($id);
      $trip_order->set('order_object', [
        'value' => $order,
        'format' => 'NULL',
      ]);
      $trip_order->save();
    }

    return new JsonResponse($hash);
  }

}
