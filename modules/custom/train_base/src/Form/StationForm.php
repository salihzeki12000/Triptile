<?php

namespace Drupal\train_base\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Station edit forms.
 *
 * @ingroup train_base
 */
class StationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\train_base\Entity\Station */
    $form = parent::buildForm($form, $form_state);

    // Do not require address for a parent station.
    if ($this->getRequest()->get('parent', false)) {
      $form['address']['#access'] = false;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Station.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Station.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.station.canonical', ['station' => $entity->id()]);
  }

}
