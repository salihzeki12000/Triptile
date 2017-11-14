<?php

namespace Drupal\train_base\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Supplier edit forms.
 *
 * @ingroup train_base
 */
class SupplierForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\train_base\Entity\Supplier */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $createPayableInvoice = $form_state->getValue('create_payable_invoice');
    $runningBalanceId = $form_state->getValue('running_balance_id');
    if ($createPayableInvoice['value'] && $runningBalanceId[0]['value']) {
      $form_state->setError($form['create_payable_invoice']['widget'], $this->t('Supplier should store only @pi or only @rb.',
        ['@pi' => $form['create_payable_invoice']['widget']['#title'], '@rb' => $form['running_balance_id']['widget']['#title']]));
      $form_state->setError($form['running_balance_id']['widget']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Supplier.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Supplier.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.supplier.canonical', ['supplier' => $entity->id()]);
  }

}
