<?php

namespace Drupal\train_provider\Form;

use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\plugin\PluginDefinition\PluginOperationsProviderDefinitionInterface;
use Drupal\plugin\PluginDiscovery\TypedDefinitionEnsuringPluginDiscoveryDecorator;
use Drupal\plugin\PluginType\PluginTypeManager;
use Drupal\train_provider\TrainProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TrainProvidersForm extends FormBase {

  /**
   * @var \Drupal\train_provider\TrainProviderManager
   */
  protected $trainProviderManager;

  /**
   * @var \Drupal\plugin\PluginType\PluginTypeManager
   */
  protected $pluginTypeManager;

  /**
   * @var \Drupal\Core\DependencyInjection\ClassResolver
   */
  protected $classResolver;

  /**
   * TrainProvidersForm constructor.
   *
   * @param \Drupal\train_provider\TrainProviderManager $train_provider_manager
   * @param \Drupal\plugin\PluginType\PluginTypeManager $plugin_type_manager
   * @param \Drupal\Core\DependencyInjection\ClassResolver $class_resolver
   */
  public function __construct(TrainProviderManager $train_provider_manager, PluginTypeManager $plugin_type_manager, ClassResolver $class_resolver) {
    $this->trainProviderManager = $train_provider_manager;
    $this->pluginTypeManager = $plugin_type_manager;
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.train_provider'),
      $container->get('plugin.plugin_type_manager'),
      $container->get('class_resolver')
    );
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo Is there a better way to get definitions?
    $pluginDiscovery = new TypedDefinitionEnsuringPluginDiscoveryDecorator($this->pluginTypeManager->getPluginType('train_provider'));
    $trainProviders = $pluginDiscovery->getDefinitions();

    $form['train_providers'] = array(
      '#header' => array($this->t('Title'), $this->t('Enabled'), $this->t('Booking available'), $this->t('Operations')),
      '#type' => 'table',
    );

    foreach ($trainProviders as $trainProvider => $definition) {
      $configs = $this->configFactory()->get('plugin.plugin_configuration.train_provider.' . $trainProvider);

      $form['train_providers'][$trainProvider]['label'] = array(
        '#description' => $definition['description'],
        '#markup' => $definition['label'],
        '#title' => $this->t('Title'),
        '#title_display' => 'invisible',
        '#type' => 'item',
      );
      $form['train_providers'][$trainProvider]['status'] = array(
        '#default_value' => $configs->get('status'),
        '#title' => $this->t('Enabled'),
        '#title_display' => 'invisible',
        '#type' => 'checkbox',
      );
      $form['train_providers'][$trainProvider]['booking_available'] = array(
        '#default_value' => $configs->get('booking_available'),
        '#title' => $this->t('Booking available'),
        '#title_display' => 'invisible',
        '#type' => 'checkbox',
      );
      $links = [];
      if ($definition instanceof PluginOperationsProviderDefinitionInterface) {
        $operations_provider = $this->classResolver->getInstanceFromDefinition($definition->getOperationsProviderClass());
        $links = $operations_provider->getOperations($trainProvider);
      };
      $form['train_providers'][$trainProvider]['operations'] = array(
        '#links' => $links,
        '#title' => $this->t('Operations'),
        '#type' => 'operations',
      );
    }

    $trainProviderSettings = $this->configFactory()->get('train_provider.settings');
    $form['min_departure_window_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Minimal departure window'),
    ];
    $form['min_departure_window_fieldset']['min_days_before_departure'] = [
      '#title' => $this->t('Number of days'),
      '#type' => 'number',
      '#default_value' => $trainProviderSettings->get('common_min_days_before_departure'),
      '#min' => 0,
    ];
    $form['min_departure_window_fieldset']['min_hours_before_departure'] = [
      '#title' => $this->t('Number of hours'),
      '#type' => 'number',
      '#default_value' => $trainProviderSettings->get('common_min_hours_before_departure'),
      '#min' => 0,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('train_providers') as $trainProvider => $values) {
      $this->configFactory()
        ->getEditable('plugin.plugin_configuration.train_provider.' . $trainProvider)
        ->set('status', (bool) $values['status'])
        ->set('booking_available', (bool) $values['booking_available'])
        ->save();
    }

    $config = $this->configFactory()->getEditable('train_provider.settings');
    $min_days_before_departure = $form_state->getValue('min_days_before_departure');
    $config->set('common_min_days_before_departure', $min_days_before_departure);
    $min_hours_before_departure = $form_state->getValue('min_hours_before_departure');
    $config->set('common_min_hours_before_departure', $min_hours_before_departure);
    $config->save();
  }

}
