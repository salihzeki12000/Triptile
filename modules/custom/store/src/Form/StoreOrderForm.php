<?php

namespace Drupal\store\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\salesforce\Form\SalesforceSyncEntityFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\salesforce\SalesforceSync;
use Drupal\salesforce\Plugin\SalesforceMappingManager;

/**
 * Form controller for Store order edit forms.
 *
 * @ingroup store
 */
class StoreOrderForm extends ContentEntityForm {

  use SalesforceSyncEntityFormTrait {
    buildForm as protected buildFormTrait;
  }

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $this->buildFormTrait($form, $form_state);
    /** @var \Drupal\store\Entity\StoreOrder $order */
    $order = $this->entity;
    if ($order->bundle() == 'train_order') {
      $form['order2'] = [
        '#type' => 'checkbox',
        '#default_value' => $order->getData('Order2 exported'),
        '#title' => $this->t('Order2 created'),
        '#disabled' => $order->getData('Order2 exported'),
        '#weight' => 13,
      ];
      $form['payable_invoice'] = [
        '#type' => 'checkbox',
        '#default_value' => $order->getData('Payable Invoice exported'),
        '#title' => $this->t('Payable invoice created'),
        '#disabled' => $order->getData('Payable Invoice exported'),
        '#weight' => 13,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\store\Entity\StoreOrder $order */
    $order = parent::validateForm($form, $form_state);

    if ($order->bundle() == 'train_order' && count($order->getPdfFiles()) == 0 && $order->getStatus() == $order::STATUS_BOOKED) {
      $form_state->setError($form['status'], $this->t('You cannot set status @status if no PDF file is attached to the order.', ['@status' => $order::getStatusName($order::STATUS_BOOKED)]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\store\Entity\StoreOrder $order */
    $order = $this->entity;
    if ($order->bundle() == 'train_order') {
      // Order2 can create manually in the SalesForce directly. So we need to prevent creating Order2 twice.
      if ($form_state->getValue('order2') && !$order->getData('Order2 exported')) {
        $order->setData('Order2 exported', true);
      }
      // Payable invoice can create manually in the SalesForce directly. So we need to prevent creating payable invoice twice.
      if ($form_state->getValue('payable_invoice') && !$order->getData('Payable Invoice exported')) {
        $order->setData('Payable Invoice exported', true);
      }
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Store order.', [
          '%label' => $order->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Store order.', [
          '%label' => $order->label(),
        ]));
    }

    // Trigger store order to creating salesforce mapping object.
    $this->salesforceBaseTrigger($form_state, $order);

    $form_state->setRedirect('entity.store_order.canonical', ['store_order' => $order->id()]);
  }

}
