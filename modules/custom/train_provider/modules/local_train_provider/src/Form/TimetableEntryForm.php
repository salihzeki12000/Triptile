<?php

namespace Drupal\local_train_provider\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Timetable entry edit forms.
 *
 * @ingroup local_train_provider
 */
class TimetableEntryForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\local_train_provider\Entity\TimetableEntry */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

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
        drupal_set_message($this->t('Created the %label Timetable entry.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Timetable entry.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.timetable_entry.canonical', ['timetable_entry' => $entity->id()]);
  }

}
