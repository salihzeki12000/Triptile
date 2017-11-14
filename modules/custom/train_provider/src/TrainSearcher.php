<?php
 
namespace Drupal\train_provider;

use Drupal\Core\Config\ConfigFactoryInterface;
 
class TrainSearcher {

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
   * Constructor.
   *
   * @param \Drupal\train_provider\TrainProviderManager $train_provider_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(TrainProviderManager $train_provider_manager, ConfigFactoryInterface $config_factory) {
    $this->trainProviderManager = $train_provider_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Gets the timetable with available variants as user requested.
   *
   * @param array $search_configuration
   * @return \Drupal\train_provider\RouteInfoHolder[]
   */
  public function getTimetable($search_configuration) {
    $trains = [1 => [], 2 => []];
    $output = [];
    foreach ($this->trainProviderManager->getDefinitions() as $pluginId => $definition) {
      $train_provider_configuration = $this->configFactory
        ->get('train_provider.settings')
        ->get();
      $plugin_configuration = $this->configFactory
        ->get('plugin.plugin_configuration.train_provider.' . $pluginId)
        ->get();
      if (!empty($plugin_configuration['status'])) {
        $configuration = array_merge($search_configuration, $train_provider_configuration, $plugin_configuration);
        /** @var \Drupal\train_provider\TrainProviderBase $trainProvider */
        $trainProvider = $this->trainProviderManager->createInstance($pluginId, $configuration);
        $providerTrains = $trainProvider->getTimeTable();

        // Provider can return complex trip result. Need to relate it clear.
        foreach ($providerTrains as $leg => $providerTrain) {
          $trains[$leg] = array_merge($trains[$leg], $providerTrain);
        }
      }
    }
    foreach ($search_configuration['legs'] as $leg => $legData) {
      $route_class = new RouteInfoHolder();
      $route_class->setTrains($trains[$leg]);
      $route_class->setDepartureStation($legData['departure_station']);
      $route_class->setArrivalStation($legData['arrival_station']);
      $route_class->setDepartureDate($legData['departure_date']);
      $output[$leg] = $route_class;
    }

    return $output;
  }

}
