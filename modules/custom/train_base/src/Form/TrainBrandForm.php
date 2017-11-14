<?php

namespace Drupal\train_base\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\train_base\Entity\TrainBrand;

/**
 * Form controller for Train brand edit forms.
 *
 * @method TrainBrand getEntity()
 *
 * @ingroup train_base
 */
class TrainBrandForm extends ContentEntityForm {
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    
    $status = parent::save($form, $form_state);
    
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Train brand.', [
          '%label' => $entity->label(),
        ]));
        break;
      
      default:
        drupal_set_message($this->t('Saved the %label Train brand.', [
          '%label' => $entity->label(),
        ]));
    }
    
    $form_state->setRedirect('entity.train_brand.canonical', [
      'train_brand' => $entity->id(),
    ]);
  }
  
}
