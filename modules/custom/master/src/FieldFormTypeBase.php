<?php

namespace Drupal\master;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Base class for Field form type plugins.
 */
abstract class FieldFormTypeBase extends PluginBase implements FieldFormTypeInterface {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormElements($parameters = []) {
    $form = [];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * Return true if form is complex (consists a lot of fields).
   *
   * @return bool
   */
  public function isComplexForm() {
    return false;
  }

}
