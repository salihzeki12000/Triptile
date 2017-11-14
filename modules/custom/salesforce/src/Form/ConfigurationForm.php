<?php

namespace Drupal\salesforce\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\salesforce\SalesforceApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigurationForm
 *
 * @package Drupal\salesforce\Form
 */
class ConfigurationForm extends ConfigFormBase  {

  /**
   * @var \Drupal\salesforce\SalesforceApi
   */
  protected $salesforceApi;

  /**
   * ConfigurationForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\salesforce\SalesforceApi $salesforce_api
   */
  public function __construct(ConfigFactoryInterface $config_factory, SalesforceApi $salesforce_api) {
    parent::__construct($config_factory);

    $this->salesforceApi = $salesforce_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('salesforce_api'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['salesforce.credentials'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'salesforce_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('salesforce.credentials');

    $form['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer key'),
      '#required' => true,
      '#default_value' => $config->get('consumer_key'),
    ];
    $form['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer secret'),
      '#required' => true,
      '#default_value' => $config->get('consumer_secret'),
    ];
    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Endpoint'),
      '#required' => true,
      '#default_value' => $config->get('endpoint') ? : 'https://login.salesforce.com',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('salesforce.credentials');

    foreach (['consumer_key', 'consumer_secret', 'endpoint'] as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    $redirect = new TrustedRedirectResponse($this->salesforceApi->getRedirectUrl()->toString());
    $form_state->setResponse($redirect);
  }

}
