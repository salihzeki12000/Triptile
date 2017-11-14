<?php

namespace Drupal\express3_train_provider;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\train_provider\TrainProviderManager;
use stdClass;

class LongCache {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\train_provider\TrainProviderManager
   */
  protected $trainProviderManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * LongCache constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\train_provider\TrainProviderManager $train_provider_manager
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(EntityTypeManager $entity_type_manager, TrainProviderManager $train_provider_manager, ConfigFactory $config_factory, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->trainProviderManager = $train_provider_manager;
    $this->configFactory = $config_factory;
    $this->database = $database;
  }

  /**
   * Requests getTimetable() from express3_train_provider with $longCache parameter true,
   * so allow to create long cache on the provider side.
   */
  public function create() {
    // Base common configurations
    $trainProviderConfiguration = $this->configFactory
      ->get('train_provider.settings')
      ->get();

    // Own plugin configurations.
    $pluginConfiguration = $this->configFactory
      ->get('plugin.plugin_configuration.train_provider.express3_train_provider')
      ->get();

    // Search configuration.
    $searchConfiguration = [
      'adult_number' => 1,
      'child_number' => 0,
      'round_trip' => true,
      'complex_trip' => true,
    ];

    // Choose list of the most popular routes.
    $query = $this->database->select('success_search_detailed', 'ssd');
    $query->addField('ssd', 'departure_station', 'departure_station');
    $query->addField('ssd', 'arrival_station', 'arrival_station');
    $query->addExpression('SUM(ssd.count)', 'count');
    $query->groupBy('departure_station');
    $query->groupBy('arrival_station');
    $query->orderBy('count', 'DESC');
    $query->range(0, $pluginConfiguration['long_cache']['max_routes']);
    $routes = $query->execute()->fetchAll();

    $processedRoutes = [];
    foreach ($routes as $route) {
      $departureStation = $this->entityTypeManager->getStorage('station')->load($route->departure_station);
      $arrivalStation = $this->entityTypeManager->getStorage('station')->load($route->arrival_station);

      // We make roundtrip getTimetable request. So we have to skip reversal popular routes.
      foreach ($processedRoutes as $processedRoute) {
        if ($processedRoute->arrival_station == $route->departure_station &&
          $processedRoute->departure_station == $route->arrival_station) {
          continue 2;
        }
      }
      $processedRoutes[] = $route;

      $departureDate = DrupalDateTime::createFromTimestamp(strtotime(date("Y-m-d")));
      for ($i = 1; $i < $pluginConfiguration['max_days_before_departure']; $i++) {
        // Search configuration.
        for ($leg = 1; $leg <= 2; $leg++) {
          $searchConfiguration['legs'][$leg]['departure_station'] = $leg == 1 ? $departureStation : $arrivalStation;
          $searchConfiguration['legs'][$leg]['arrival_station'] = $leg == 1 ? $arrivalStation : $departureStation;
          $searchConfiguration['legs'][$leg]['departure_date'] = $departureDate;
        }

        // Merge all configurations
        $configuration = array_merge($searchConfiguration, $trainProviderConfiguration, $pluginConfiguration);
        /** @var \Drupal\express3_train_provider\Plugin\TrainProvider\Express3TrainProvider $plugin */
        $plugin = $this->trainProviderManager->createInstance('express3_train_provider', $configuration);
        $plugin->getTimeTable(true);

        // Increased departureDate on 1 day.
        $departureDate->modify('+1 day');
      }
    }
  }
}