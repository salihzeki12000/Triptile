<?php

namespace Drupal\master\Form\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\currency\FormHelperInterface;

class ConfigForm extends FormBase {

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
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * The form helper.
   *
   * @var \Drupal\currency\FormHelperInterface
   */
  protected $formHelper;


  /**
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   The country manager.
   * @param \Drupal\currency\FormHelperInterface $form_helper
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager, CountryManagerInterface $country_manager, FormHelperInterface $form_helper) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->countryManager = $country_manager;
    $this->formHelper = $form_helper;

  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('entity_type.manager'), $container->get('country_manager'), $container->get('currency.form_helper'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'master_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['main'] = [
      '#markup' => $this->t('Hello, World!'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
