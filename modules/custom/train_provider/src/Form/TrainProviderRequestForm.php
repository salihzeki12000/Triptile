<?php

namespace Drupal\train_provider\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Detailed success search edit forms.
 *
 * @ingroup train_provider
 */
class TrainProviderRequestForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\train_provider\Entity\TrainProviderRequest */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

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
        drupal_set_message($this->t('Created the %label search statistic.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label search statistic.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.train_provider_request.canonical', ['train_provider_request' => $entity->id()]);
  }

}
