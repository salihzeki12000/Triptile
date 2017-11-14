<?php

namespace Drupal\rn_user\Form;

use Drupal\user\ProfileForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\salesforce\Form\SalesforceSyncEntityFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\salesforce\SalesforceSync;
use Drupal\salesforce\Plugin\SalesforceMappingManager;

class UserForm extends ProfileForm {

  use SalesforceSyncEntityFormTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * @var \Drupal\salesforce\SalesforceSync
   */
  protected $salesforceSync;

  /**
   * @var \Drupal\salesforce\Plugin\SalesforceMappingManager
   */
  protected $mappingManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\rn_user\Form\UserForm $instance */
    $instance = parent::create($container);
    $instance->setSalesforceSync($container->get('salesforce_sync'))
      ->setSalesforceMappingManager($container->get('plugin.manager.salesforce_mapping'));
    return $instance;
  }

  /**
   * @param \Drupal\salesforce\SalesforceSync $salesforce_sync
   * @return static
   */
  public function setSalesforceSync(SalesforceSync $salesforce_sync) {
    $this->salesforceSync = $salesforce_sync;
    return $this;
  }

  /**
   * @param \Drupal\salesforce\Plugin\SalesforceMappingManager $salesforce_mapping_manager
   * @return static
   */
  public function setSalesforceMappingManager(SalesforceMappingManager $salesforce_mapping_manager) {
    $this->mappingManager = $salesforce_mapping_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $entity = $this->entity;
    $this->salesforceBaseTrigger($form_state, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $user = $this->currentUser();
    $form['account']['mail']['#weight'] = 1;
    $form['account']['current_pass']['#weight'] = 2;
    $form['account']['pass']['#weight'] = 3;
    $form['language']['#access'] = $user->hasPermission('administer users');
    return $form;
  }

}
