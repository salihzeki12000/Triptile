<?php

namespace Drupal\tt_config\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\JsonResponse;

class LoadEntities extends ControllerBase{

  const
    ENTITY_IS_PUBLISHED = 1,
    ENTITY_IS_PREFERRED = 1,
    HUB_DEFAULT_DAYS = 3;

  /*
   * Callback for load all hubs json
   */
  public function hubs() {

    $ids = \Drupal::entityQuery('hub')->sort('id')->execute();
    $load_hubs = \Drupal\trip_base\Entity\Hub::loadMultiple($ids);

    $hubs = [];
    foreach ($load_hubs as $load_hub) {
      $id = $load_hub->id();
      $image = $load_hub->get('field_image')->getValue();
      $fid = $image[0]['target_id'] ?? '';
      $hubs[$id] = (object) [
        'id' => $load_hub->id(),
        'name' => $load_hub->getName(),
        'rating' => $load_hub->getRating(),
        'country' => $load_hub->getCountry(),
        'description__value' => $load_hub->getDescription(),
        'field_image_target_id' => $fid,
        'days' => !empty($load_hub->getRecommendedNumberOfDays()) ? (int) $load_hub->getRecommendedNumberOfDays() : static::HUB_DEFAULT_DAYS,
      ];
    }

    $country_name = \Drupal::service('country_manager')->getList();

    $regions = \Drupal::config('tt_config.config')->get();
    unset($regions['_core']);

    // Set region and
    foreach ($hubs as $key => $hub) {
      $hubs[$key]->country_name = $country_name[$hub->country];

      $fid = $hub->field_image_target_id;
      unset($hub->field_image_target_id);
      if($fid){
        $url = File::load($fid)->url();
        $hub->img = $url;
      }

      foreach($regions as $region_name => $region){
        if(array_key_exists($hub->country, $region)){
          $hubs[$key]->region = $region_name;
        }
      }
    }

    return new JsonResponse($hubs);
  }

  /*
   * Callback for load one hub json
   */
  public function oneHub($hub_id) {

    $query = \Drupal::database()->select('hub_field_data', 'h');
    $query->fields('h', ['id', 'name', 'rating', 'country']);
    $query->condition('h.status', static::ENTITY_IS_PUBLISHED);
    $query->condition('h.id', $hub_id);
    $hubs = $query->execute()->fetchAllAssoc('id');

    $load_hub = \Drupal\trip_base\Entity\Hub::load($hub_id);

    $hubs = [];
    $id = $load_hub->id();
    $image = $load_hub->get('field_image')->getValue();
    $fid = $image[0]['target_id'] ?? '';
    $hubs[$id] = (object) [
      'id' => $load_hub->id(),
      'name' => $load_hub->getName(),
      'rating' => $load_hub->getRating(),
      'country' => $load_hub->getCountry(),
      'description__value' => $load_hub->getDescription(),
      'field_image_target_id' => $fid,
      'days' => !empty($load_hub->getRecommendedNumberOfDays()) ? (int) $load_hub->getRecommendedNumberOfDays() : static::HUB_DEFAULT_DAYS,
    ];

    $country_name = \Drupal::service('country_manager')->getList();

    $regions = \Drupal::config('tt_config.config')->get();
    unset($regions['_core']);

    // Set region and
    foreach ($hubs as $key => $hub) {
      $hubs[$key]->country_name = $country_name[$hub->country];

      foreach($regions as $region_name => $region){
        if(array_key_exists($hub->country, $region)){
          $hubs[$key]->region = $region_name;
        }
      }
    }

    return new JsonResponse($hubs);
  }

  /*
   * Load entity for hub
   */
  public function entityByHub($hub_id, $entity_type, $fields, $hub_field_name = 'hub') {

    $plural = [
      'hotel' => 'hotels',
      'transfer' => 'transfers',
      'activity' => 'activities',
      'connection' => 'connections'
    ];

    $langcode =  \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Load all entities by hub
    $query = \Drupal::database()->select($entity_type . '_field_data', 'e');
    $query->fields('e', $fields);
    $query->condition('e.status', static::ENTITY_IS_PUBLISHED);
    $query->condition('e.' . $hub_field_name, $hub_id);
    $query->condition('e.langcode', $langcode);
    $entities = $query->execute()->fetchAllAssoc('id');

    $entity_ids = [];
    foreach($entities as $entity){
      $entity_ids[] = $entity->id;
    }

    if(sizeof($entity_ids) > 0){

      $preferred = $this::getPreferredEntity($entity_type, $hub_id, $fields, $hub_field_name, $langcode);

      // Get prices and prices mapping for entities
      $prices_array = $this::loadPricesForEntity($entity_type, $entity_ids);

      foreach($prices_array['prices_mapping'] as $map){
        $entity_id = $map->entity_id;
        $entities[$entity_id]->price_options = explode(',', $map->price_options);
      }

      // Output entities and prices
      $output = [$plural[$entity_type] => $entities, 'prices' => $prices_array['prices'], 'preferred' => $preferred];
    }
    else{
      $output = [$plural[$entity_type] => '', 'prices' => '', 'preferred' => ''];
    }

    return $output;
  }


  /*
   * Load prices for entity
   */
  public function loadPricesForEntity($entity_type, $entity_ids) {

    $langcode =  \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Load all prices for prices mapping
    $query = \Drupal::database()->select($entity_type . '__price_options', 'hpo');
    $query->fields('hpo', ['entity_id']);
    $query->groupBy('hpo.entity_id');
    $query->addExpression('GROUP_CONCAT(hpo.price_options_target_id)', 'price_options');
    $query->condition('hpo.entity_id', $entity_ids, 'IN');
    $query->condition('hpo.langcode', $langcode);
    $prices_mapping = $query->execute()->fetchAll();

    // Load all prices for entity type
    $query = \Drupal::database()->select($entity_type . '__price_options', 'hpo');
    $query->distinct();
    $query->fields('hpo', ['price_options_target_id', 'entity_id']);
    $query->condition('hpo.entity_id', $entity_ids, 'IN');
    $prices = $query->execute()->fetchAll();

    $prices_ids = [];
    foreach($prices as $price){
      $prices_ids[] = $price->price_options_target_id;
    }

    $prices = [];
    if(sizeof($prices_ids) > 0){
      $query = \Drupal::database()->select('base_product_field_data', 'bpfd');
      $query->leftJoin('base_product__available_from_date', 'faf', 'faf.entity_id = bpfd.id');
      $query->leftJoin('base_product__available_until_date', 'fau', 'fau.entity_id = bpfd.id');
      $query->fields('bpfd', ['id', 'name', 'price__number', 'price__currency_code', 'max_quantity', 'preferred']);
      $query->addField('faf', 'available_from_date_value', 'available_from');
      $query->addField('fau', 'available_until_date_value', 'available_until');
      $query->condition('bpfd.id', $prices_ids, 'IN');
      $query->condition('bpfd.status', static::ENTITY_IS_PUBLISHED);
      $query->condition('bpfd.langcode', $langcode);
      $prices = $query->execute()->fetchAllAssoc('id');
    }

    $rates = \Drupal::config('currency.exchanger.fixed_rates')->getRawData()['rates'];
    foreach ($prices as $price){
      $price_currency_code = $price->price__currency_code;
      foreach ($rates as $rate){
        if ($rate['currency_code_from'] == $price_currency_code && $rate['currency_code_to'] == 'USD'){
          $price->price__number = (string)((float)$price->price__number * (float)$rate['rate']);
        }
      }
    }

    return ['prices_mapping' => $prices_mapping, 'prices' => $prices];
  }


  /*
   * Get preferred entity for hub
   */
  public function getPreferredEntity($entity_type, $hub_id, $fields, $hub_field_name, $langcode) {
    $entities = '';

    if($entity_type == 'hotel' || $entity_type == 'transfer' || $entity_type == 'activity'){
      $query = \Drupal::database()->select($entity_type . '_field_data', 'e');
      $query->fields('e', ['id']);
      $query->condition('e.status', static::ENTITY_IS_PUBLISHED);
      $query->condition('e.preferred', static::ENTITY_IS_PREFERRED);
      $query->condition('e.' . $hub_field_name, $hub_id);
      $query->condition('e.langcode', $langcode);
      $query->range(0, 1);
      $entities = $query->execute()->fetchField();
    }
    else if($entity_type == 'connection'){
      $query = \Drupal::database()->select($entity_type . '_field_data', 'e');
      $query->fields('e', ['id']);
      $query->condition('e.status', static::ENTITY_IS_PUBLISHED);
      $query->condition('e.' . $hub_field_name, $hub_id);
      $query->condition('e.langcode', $langcode);
      $query->orderBy('e.rating', 'DESC');
      $query->range(0, 1);
      $entities = $query->execute()->fetchField();
    }

    return $entities;
 }

  /*
  * Callback for load hotels by hub json
  */
  public function hotelsByHub($hub_id) {
    $output = $this::entityByHub($hub_id, 'hotel', ['id', 'name', 'hub', 'preferred', 'star', 'description__value']);
    return new JsonResponse($output);
  }

  /*
  * Callback for load transfers by hub json
  */
  public function transfersByHub($hub_id) {
    $output = $this::entityByHub($hub_id, 'transfer', ['id', 'name', 'preferred']);
    return new JsonResponse($output);
  }

  /*
  * Callback for load activities by hub json
  */
  public function activitiesByHub($hub_id) {
    $output = $this::entityByHub($hub_id, 'activity', ['id', 'name', 'preferred']);
    return new JsonResponse($output);
  }

  /*
  * Callback for load connections by hub json
  */
  public function connectionsByHub($hub_id) {
    $output = $this::entityByHub(
      $hub_id,
      'connection',
      ['id', 'name', 'point_1', 'point_2', 'type', 'duration', 'rating', 'overall_rating', 'preferred'],
      'point_1'
    );

    return new JsonResponse($output);
  }

  /*
   * Load order from hash
   */
  public function loadOrder($hash) {
    $query = \Drupal::database()->select('trip_order', 't');
    $query->addField('t', 'order_object__value');
    $query->condition('t.hash', $hash);
    $query->range(0, 1);
    $order_object = $query->execute()->fetchField();

    return new JsonResponse(json_decode($order_object));
  }

}
