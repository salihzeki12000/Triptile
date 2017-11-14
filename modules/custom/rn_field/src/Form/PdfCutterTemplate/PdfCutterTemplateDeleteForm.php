<?php

namespace Drupal\rn_field\Form\PdfCutterTemplate;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete pdf cutter template entities.
 */
class PdfCutterTemplateDeleteForm extends EntityConfirmFormBase {
  
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', [
      '%name' => $this->getEntity()->label(),
    ]);
  }
  
  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('entity.pdf_cutter_template.collection');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    $entity = $this->getEntity();
    
    $entity->delete();
    
    drupal_set_message($this->t('content @type: deleted @label.', [
      '@type' => $entity->bundle(),
      '@label' => $entity->label(),
    ]));
    
    $formState->setRedirectUrl($this->getCancelUrl());
  }
  
}
