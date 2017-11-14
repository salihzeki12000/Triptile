<?php

namespace Drupal\train_provider\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\train_provider\TrainProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TrainProviderRouteDataForm extends FormBase {

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
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\train_provider\TrainProviderManager $train_provider_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(TrainProviderManager $train_provider_manager, ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager) {
    $this->trainProviderManager = $train_provider_manager;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.train_provider'), $container->get('config.factory'), $container->get('entity_type.manager'));
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'train_provider_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $train_provider = NULL) {
    if (!$train_provider) {
      throw new \InvalidArgumentException('Train provider is not exists');
    }

    $form_state->set('pluginId', $train_provider);

    $form['departure_station'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Departure from'),
      '#target_type' => 'station',
      //'#default_value' => $this->entityTypeManager->getStorage('station')->load(37),
    ];
    $form['arrival_station'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Arrival to'),
      '#target_type' => 'station',
      //'#default_value' => $this->entityTypeManager->getStorage('station')->load(104),
    ];
    $form['departure_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Departure Date'),
      //'#default_value' => DrupalDateTime::createFromTimestamp(time())->format(DATETIME_DATE_STORAGE_FORMAT),
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Return back'),
      '#url' => new Url('train_provider.config.train_providers'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $departureStation = $this->entityTypeManager->getStorage('station')->load($values['departure_station']);
    $search_configuration = [
      'legs' => [
        1 => [
          'departure_station' => $departureStation,
          'arrival_station' => $this->entityTypeManager->getStorage('station')->load($values['arrival_station']),
          'departure_date' => new DrupalDateTime($values['departure_date'], $departureStation->getTimezone()),
        ],
      ],
      'adult_number' => 1,
      'child_number' => 0,
      'round_trip' => false,
      'complex_trip' => false,
    ];

    $pluginId = $form_state->get('pluginId');
    $train_provider_configuration = $this->configFactory
      ->get('train_provider.settings')
      ->get();
    $plugin_configuration = $this->configFactory
      ->get('plugin.plugin_configuration.train_provider.' . $pluginId)
      ->get();
    $configuration = array_merge($search_configuration, $train_provider_configuration, $plugin_configuration);
    /** @var \Drupal\train_provider\TrainProviderBase $trainProvider */
    $trainProvider = $this->trainProviderManager->createInstance($pluginId, $configuration);
    $routeData = $trainProvider->getRouteData();
    $form_state->setRebuild(true);
    $output['admin_filtered_string'] = [
      '#markup' => $routeData,
    ];
    drupal_set_message($output, 'status');
  }

  public function title($train_provider = NULL) {
    if (!$train_provider) {
      throw new \InvalidArgumentException('Train provider is not exists');
    }

    $definition = $this->trainProviderManager->getDefinition($train_provider);

    return $this->t('@train_provider@ configuration', ['@train_provider@' => $definition['label']]);
  }

}