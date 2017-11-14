<?php

namespace Drupal\train_provider\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\train_provider\TrainProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TrainProviderConfigurationForm extends FormBase {

  /**
   * @var \Drupal\train_provider\TrainProviderManager
   */
  protected $trainProviderManager;

  /**
   * PaymentMethodConfigurationForm constructor.
   *
   * @param \Drupal\train_provider\TrainProviderManager $train_provider_manager
   */
  public function __construct(TrainProviderManager $train_provider_manager) {
    $this->trainProviderManager = $train_provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.train_provider')
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
  public function buildForm(array $form, FormStateInterface $form_state, $train_provider = NULL) {
    if (!$train_provider) {
      throw new \InvalidArgumentException('Train provider is not exists');
    }

    $configs = $this->configFactory()->get('plugin.plugin_configuration.train_provider.' . $train_provider)->get();
    /** @var \Drupal\train_provider\TrainProviderBase $plugin */
    $plugin = $this->trainProviderManager->createInstance($train_provider, $configs);
    $form = $plugin->buildConfigurationForm($form, $form_state);
    $form_state->set('plugin', $plugin);

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => new Url('train_provider.config.train_providers'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\train_provider\TrainProviderBase $plugin */
    $plugin = $form_state->get('plugin');
    $plugin->submitConfigurationForm($form, $form_state);
    $this->configFactory()
      ->getEditable('plugin.plugin_configuration.train_provider.' . $plugin->getBaseId())
      ->setData($plugin->getConfiguration())
      ->save();
  }

  public function title($train_provider = NULL) {
    if (!$train_provider) {
      throw new \InvalidArgumentException('Train provider is not exists');
    }

    $definition = $this->trainProviderManager->getDefinition($train_provider);

    return $this->t('@train_provider@ configuration', ['@train_provider@' => $definition['label']]);
  }

}
