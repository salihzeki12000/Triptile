<?php

namespace Drupal\local_train_provider;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\train_provider\TrainProviderManager;
use Drupal\train_provider\TrainSearcher;

class TimetableEntryPriceUpdater {

  const PRICE_UPDATE_PERIOD = 86400; // 24 hours

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\train_provider\TrainSearcher
   */
  protected $trainSearcher;

  /**
   * @var \Drupal\train_provider\TrainProviderManager
   */
  protected $trainProviderManager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $timetableEntryStorage;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\train_provider\TrainSearcher $train_searcher
   * @param \Drupal\train_provider\TrainProviderManager $train_provider_manager
   */
  public function __construct(EntityTypeManager $entity_type_manager, ConfigFactoryInterface $config_factory, TrainSearcher $train_searcher, TrainProviderManager $train_provider_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->trainSearcher = $train_searcher;
    $this->trainProviderManager = $train_provider_manager;
    $this->timetableEntryStorage = $this->entityTypeManager->getStorage('timetable_entry');
  }

  /**
   * Updates price using a specified train provider. If train provider is not specified,
   * it use all train providers which are price updaters.
   *
   * @param string $pluginId
   */
  public function update($pluginId = '') {
    $timetableEntriesCount = $this->timetableEntryStorage->getQuery()->count()->execute();
    $neededPart = round($timetableEntriesCount / 10);
    $query = $this->timetableEntryStorage->getQuery();
    $priceUpdateTimestampCondition = $query->orConditionGroup()
      ->notExists('price_update_timestamp')
      ->condition('price_update_timestamp', time() - static::PRICE_UPDATE_PERIOD, '<=');
    $pluginId ? $query->condition('price_update', $pluginId) : $query->condition('price_update', 'disabled', '!=');
    $query->condition($priceUpdateTimestampCondition);
    $query->range(0, $neededPart);
    $timetableEntriesIds = $query->execute();
    $timetableEntries = $this->timetableEntryStorage->loadMultiple($timetableEntriesIds);
    /** @var \Drupal\local_train_provider\Entity\TimetableEntryInterface $timetableEntry */
    foreach ($timetableEntries as $timetableEntry) {
      if ($timetableEntry->getLastPriceUpdateTimestamp() + static::PRICE_UPDATE_PERIOD >= time()) {
        continue;
      }
      $pluginId = $timetableEntry->getPriceUpdater();
      $configuration = $this->getConfiguration($pluginId, $timetableEntry);
      /** @var \Drupal\train_provider\TrainProviderBase $trainProvider */
      $trainProvider = $this->trainProviderManager->createInstance($pluginId, $configuration);
      if ($trainProvider && $trainProvider->getPluginDefinition()['price_updater']) {
        $trains = $trainProvider->getTimeTable();
        /** @var \Drupal\train_provider\TrainInfoHolder $train */
        foreach ($trains[1] as $train) {
          $query = $this->timetableEntryStorage->getQuery();
          $query->condition('departure_station', $train->getDepartureStation()->id());
          $query->condition('arrival_station', $train->getArrivalStation()->id());
          $query->condition('price_update', $pluginId);
          $query->condition($priceUpdateTimestampCondition);
          $timetableEntriesForThisRouteIds = $query->execute();
          $timetableEntriesForThisRoute = $this->timetableEntryStorage->loadMultiple($timetableEntriesForThisRouteIds);
          /** @var  $timetableEntryForThisRoute \Drupal\local_train_provider\Entity\TimetableEntryInterface */
          foreach ($timetableEntriesForThisRoute as $timetableEntryForThisRoute) {
            $this->updateTimetableEntryPrice($timetableEntryForThisRoute, $train->getCoachClasses());
          }
        }
      }
    }
  }

  /**
   * Update product price, if coach classes on the product and CoachClassInfoHolder
   * are identical.
   *
   * @param \Drupal\local_train_provider\Entity\TimetableEntryInterface $timetableEntry
   * @param \Drupal\train_provider\CoachClassInfoHolder[] $coachClassInfoHolders
   */
  protected function updateTimetableEntryPrice($timetableEntry, $coachClassInfoHolders) {
    foreach ($timetableEntry->getProducts() as $product) {
      foreach ($coachClassInfoHolders as $coachClassInfoHolder) {
        if ($product->getCoachClass()->id() == $coachClassInfoHolder->getCoachClass()->id()) {
          $product->setPrice($coachClassInfoHolder->getOriginalPrice());
          $product->save();
          $timetableEntry->setLastPriceUpdateTimestamp(time());
          $timetableEntry->save();
        }
      }
    }
  }

  /**
   * Merge all configurations in one.
   *
   * @param $pluginId
   * @param $timetableEntry
   * @return array
   */
  protected function getConfiguration($pluginId, $timetableEntry) {
    $searchConfiguration = $this->getSearchConfiguration($timetableEntry);

    // Base common configurations
    $trainProviderConfiguration = $this->configFactory
      ->get('train_provider.settings')
      ->get();

    // Own plugin configurations.
    $pluginConfiguration = $this->configFactory
      ->get('plugin.plugin_configuration.train_provider.' . $pluginId)
      ->get();

    // Merge all configurations
    $configuration = array_merge($searchConfiguration, $trainProviderConfiguration, $pluginConfiguration);

    return $configuration;
  }

  /**
   * Prepare search configuration based on timetable entry.
   *
   * @param \Drupal\local_train_provider\Entity\TimetableEntryInterface $timetableEntry
   * @return array
   */
  protected function getSearchConfiguration($timetableEntry) {
    $route = $this->getRouteFromTimetableEntry($timetableEntry);
    $departureDate = DrupalDateTime::createFromTimestamp(strtotime(date("Y-m-d")));
    $depthPriceUpdate = $timetableEntry->getDepthForPriceUpdate();
    if ($depthPriceUpdate > 0) {
      $departureDate->modify('+' . $depthPriceUpdate . 'days');
    }
    $searchConfiguration = [
      'adult_number' => 1,
      'child_number' => 0,
      'round_trip' => false,
      'complex_trip' => false,
      'legs' => [
        1 => [
          'departure_station' => $route['departure_station'],
          'arrival_station' => $route['arrival_station'],
          'departure_date' => $departureDate,
        ]
      ]
    ];

    return $searchConfiguration;
  }

  /**
   * Timetable can store child stations, but we need route base on parent stations.
   *
   * @param \Drupal\local_train_provider\Entity\TimetableEntryInterface $timetableEntry
   * @return array
   */
  protected function getRouteFromTimetableEntry($timetableEntry) {
    $departureStation = $timetableEntry->getDepartureStation()->getParentStation() ? : $timetableEntry->getDepartureStation();
    $arrivalStation = $timetableEntry->getArrivalStation()->getParentStation() ? : $timetableEntry->getArrivalStation();

    return ['departure_station' => $departureStation, 'arrival_station' => $arrivalStation];
  }
}