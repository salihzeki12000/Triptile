<?php

namespace Drupal\train_base;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;
use Drupal\train_base\Entity\Station;

/**
 * Class RoutePage.
 *
 * @package Drupal\train_base
 */
class RoutePage implements RoutePageInterface {

  const ROUTE_PAGE_CONTENT_TYPE = 'route_page';

  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;
  protected $stationStorage;


  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stationStorage = $entity_type_manager->getStorage('station');
  }

  /**
   * @param mixed $station_id
   * @param array $destinations
   * @return void
   */
  public function createRoutePages($station_id, $destinations = []) {
    $station = $this->loadStation($station_id);

    foreach ($destinations as $destination) {
      if (!$this->getRoutePageByStations($station, $destination)) {
        $this->generateRoutePage($station, $destination);
      }
    }
  }

  /**
   * @param \Drupal\train_base\Entity\Station $station
   * @param \Drupal\train_base\Entity\Station $destination
   * @return int
   */
  public function generateRoutePage(Station $station, Station $destination) {
    $node_params = [
      'type' => static::getRoutePageContentType(),
      'promote' => 0,
      'status' => 0,
    ];
    $node_params = array_merge($node_params, $this->generatePageParams($station, $destination));
    $node = Node::create($node_params);

    $languages = \Drupal::languageManager()->getLanguages();
    $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();

    foreach ($languages as $language => $value) {
      if ($language !== $default_language) {
        $node->addTranslation($language, $node_params);
      }
    }

    return $node->save();
  }

  /**
   * @param mixed $station_id
   * @return \Drupal\Core\Entity\EntityInterface | null
   */
  public function loadStation($station_id) {
    return $this->stationStorage->load($station_id);
  }

  /**
   * @param \Drupal\train_base\Entity\Station $departureStation
   * @param \Drupal\train_base\Entity\Station $arrivalStation
   * @return array | int
   */
  public function getRoutePageByStations(Station $departureStation, Station $arrivalStation) {
    $query = \Drupal::entityQuery('node');

    $query->condition('departure_station.target_id', $departureStation->id());
    $query->condition('arrival_station.target_id', $arrivalStation->id());
    $query->condition('type', static::getRoutePageContentType());

    return $query->execute();
  }

  /**
   * @return string
   */
  public static function getRoutePageContentType(): string {
    return self::ROUTE_PAGE_CONTENT_TYPE;
  }

  /**
   * @param \Drupal\train_base\Entity\Station $station
   * @param \Drupal\train_base\Entity\Station $destination
   * @return int | float
   */
  public function getDistance(Station $station, Station $destination) {
    $EarthRadius = 6371; // radius in km

    $lat1 = $station->getLatitude();
    $lat2 = $destination->getLatitude();
    $lon1 = $station->getLongitude();
    $lon2 = $destination->getLongitude();
    $phi1 = deg2rad($lat1);
    $phi2 = deg2rad($lat2);
    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLon = deg2rad($lon2 - $lon1);

    $a = sin($deltaLat/2) * sin($deltaLat/2) + cos($phi1) * cos($phi2) * sin($deltaLon / 2) * sin($deltaLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $EarthRadius * $c;
  }

  /**
   * @param \Drupal\train_base\Entity\Station $station
   * @param \Drupal\train_base\Entity\Station $destination
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function generatePageTitle(Station $station, Station $destination) {
    return t("@station to @destination trains", [
      '@station' => $station->getName(),
      '@destination' => $destination->getName(),
    ]);
  }

  /**
   * @param \Drupal\train_base\Entity\Station $station
   * @param \Drupal\train_base\Entity\Station $destination
   * @param string $translationLang
   * @return array
   */
  public function generatePageParams(Station $station, Station $destination, string $translationLang = ''): array {
    return [
      'departure_station' => $station,
      'arrival_station' => $destination,
      'distance' => round($this->getDistance($station, $destination)),
      'title' => $this->generatePageTitle($station, $destination),
      'path' => [
        'alias' => $this->generateTranslatedAlias($station, $destination, $translationLang)
      ],
    ];
  }

  /**
   * @param \Drupal\train_base\Entity\Station $station
   * @param \Drupal\train_base\Entity\Station $destination
   * @return string
   */
  public function generateAlias(Station $station, Station $destination): string {
    return '/route/' . static::textToUrl($station->getName()) . '-to-' . static::textToUrl($destination->getName());
  }

  /**
   * @param \Drupal\train_base\Entity\Station $station
   * @param \Drupal\train_base\Entity\Station $destination
   * @param string $lang
   * @return string
   */
  public function generateTranslatedAlias(Station $station, Station $destination, string $lang): string {
    $alias = $this->generateAlias($station, $destination);
    return !empty($lang) ? '/' . $lang . $alias : $alias;
  }

  /**
   * @param string $text
   * @return string
   */
  public static function textToUrl(string $text): string {
    return strtolower(preg_replace('/\s+/', '', $text));
  }

}
