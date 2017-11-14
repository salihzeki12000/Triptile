<?php

namespace Drupal\train_base;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Render\Element;

class TrainTicketViewBuilder extends EntityViewBuilder {

  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($build as $entity_key => &$entity_item) {
      foreach (Element::children($entity_item['departure_datetime']) as $field_item_key) {
        $entity_item['departure_datetime'][$field_item_key]['#text'] = $entities[$entity_key]->getDepartureDateTime()->format('Y-m-d H:i:s');
      }

      foreach (Element::children($entity_item['arrival_datetime']) as $field_item_key) {
        $entity_item['arrival_datetime'][$field_item_key]['#text'] = $entities[$entity_key]->getArrivalDateTime()->format('Y-m-d H:i:s');
      }
    }

  }

}
