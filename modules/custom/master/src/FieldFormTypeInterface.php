<?php

namespace Drupal\master;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Field form type plugins.
 */
interface FieldFormTypeInterface extends PluginInspectionInterface {

  /**
   * Gets field form type form.
   *
   * @param array $parameters
   * @return array
   */
  public function getFormElements($parameters = []);

  /**
   * Process validation field form type form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array $form, FormStateInterface $form_state);

  /**
   * Submit field form type form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function submitForm(array $form, FormStateInterface $form_state);

}
