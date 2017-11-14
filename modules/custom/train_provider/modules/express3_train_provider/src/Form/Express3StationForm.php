<?php

namespace Drupal\express3_train_provider\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Express3station edit forms.
 *
 * @ingroup express3_train_provider
 */
class Express3StationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\express3_train_provider\Entity\Express3Station */
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
        drupal_set_message($this->t('Created the %label Express3station.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Express3station.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.express3_station.canonical', ['express3_station' => $entity->id()]);
  }

}
