<?php

namespace Drupal\salesforce\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Salesforce mapping object edit forms.
 *
 * @ingroup salesforce
 */
class SalesforceMappingObjectForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\salesforce\Entity\SalesforceMappingObject */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    // TODO hide Salesforce object type and Entity type fields, get data from mapping plugin for the fields.

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Salesforce mapping object.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Salesforce mapping object.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.salesforce_mapping_object.canonical', ['salesforce_mapping_object' => $entity->id()]);
  }

}
