<?php

namespace Drupal\train_base\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\salesforce\Form\SalesforceSyncEntityFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\salesforce\SalesforceSync;
use Drupal\salesforce\Plugin\SalesforceMappingManager;

/**
 * Form controller for Passenger edit forms.
 *
 * @ingroup train_base
 */
class PassengerForm extends ContentEntityForm {

  use SalesforceSyncEntityFormTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\salesforce\SalesforceSync
   */
  protected $salesforceSync;

  /**
   * @var \Drupal\salesforce\Plugin\SalesforceMappingManager
   */
  protected $mappingManager;

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\salesforce\SalesforceSync $salesforce_sync
   * @param \Drupal\salesforce\Plugin\SalesforceMappingManager $mapping_manager
   */
  public function __construct(EntityManagerInterface $entity_manager, SalesforceSync $salesforce_sync, SalesforceMappingManager $mapping_manager) {
    parent::__construct($entity_manager);
    $this->salesforceSync = $salesforce_sync;
    $this->mappingManager = $mapping_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'), $container->get('salesforce_sync'), $container->get('plugin.manager.salesforce_mapping'));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Passenger.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Passenger.', [
          '%label' => $entity->label(),
        ]));
    }
    $this->salesforceBaseTrigger($form_state, $entity);
    $form_state->setRedirect('entity.passenger.canonical', ['passenger' => $entity->id()]);
  }

}
