<?php

namespace Drupal\train_base;
use Drupal\train_base\Entity\Station;

/**
 * Interface RoutePageInterface.
 *
 * @package Drupal\train_base
 */
interface RoutePageInterface {

  /**
   * Creates popular route pages if not exist
   *
   * @param mixed $station_id
   * @param \Drupal\train_base\Entity\Station[] $destinations
   * @return mixed
   */
  public function createRoutePages($station_id, $destinations);

  /**
   * Creates new node with type route_page
   *
   * @param \Drupal\train_base\Entity\Station $station
   * @param \Drupal\train_base\Entity\Station $destination
   * @return mixed
   */
  public function generateRoutePage(Station $station, Station $destination);

  /**
   * Loads station entity by id
   *
   * @param mixed $station_id
   * @return \Drupal\train_base\Entity\Station
   */
  public function loadStation($station_id);

  /**
   * Gets route page by given stations
   *
   * @param \Drupal\train_base\Entity\Station $departureStation
   * @param \Drupal\train_base\Entity\Station $arrivalStation
   * @return int
   */
  public function getRoutePageByStations(Station $departureStation, Station $arrivalStation);

  /**
   * Gets distance between departure and arrival station
   *
   * @param \Drupal\train_base\Entity\Station $station
   * @param \Drupal\train_base\Entity\Station $destination
   * @return float
   */
  public function getDistance(Station $station, Station $destination);

  /**
   * Generates route page title based on given stations
   *
   * @param \Drupal\train_base\Entity\Station $station
   * @param \Drupal\train_base\Entity\Station $destination
   * @return string
   */
  public function generatePageTitle(Station $station, Station $destination);

  /**
   * Returns array of fields for route page
   *
   * @param \Drupal\train_base\Entity\Station $station
   * @param \Drupal\train_base\Entity\Station $destination
   * @param string $translationLang
   * @return array
   */
  public function generatePageParams(Station $station, Station $destination, string $translationLang): array;

  /**
   * Generates route page alias based on stations
   *
   * @param \Drupal\train_base\Entity\Station $station
   * @param \Drupal\train_base\Entity\Station $destination
   * @return string
   */
  public function generateAlias(Station $station, Station $destination): string ;


  /**
   * Returns alias or alias with prefix for the translations
   *
   * @param \Drupal\train_base\Entity\Station $station
   * @param \Drupal\train_base\Entity\Station $destination
   * @param string $translationLang
   * @return string
   */
  public function generateTranslatedAlias(Station $station, Station $destination, string $translationLang);

}
