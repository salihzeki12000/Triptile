<?php

namespace Drupal\salesforce\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\salesforce\SalesforceSync;
use Drupal\salesforce\Entity\MappableEntityInterface;

trait SalesforceSyncEntityFormTrait {

  /**
   * @var \Drupal\salesforce\SalesforceSync
   */
  protected $salesforceSync;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if (\Drupal::currentUser()->hasPermission('synchronize objects from form')) {
      $form['salesforce_sync'] = [
        '#type' => 'checkbox',
        '#default_value' => 1,
        '#title' => $this->t('Do synchronize with SalesForce?'),
        '#weight' => 14,
      ];
    }

    return $form;
  }

  /**
   * Trigger Salesforce sync action 'PUSH'.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $entity
   */
  protected function salesforceBaseTrigger(FormStateInterface $form_state, $entity) {
    if ($form_state->getValue('salesforce_sync')) {
      $this->salesforceSync->entityCrud($entity, SalesforceSync::OPERATION_UPDATE);
    }
  }

}
