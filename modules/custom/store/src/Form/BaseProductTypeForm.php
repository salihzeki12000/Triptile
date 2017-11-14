<?php

namespace Drupal\store\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BaseProductTypeForm.
 *
 * @package Drupal\store\Form
 */
class BaseProductTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $base_product_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $base_product_type->label(),
      '#description' => $this->t("Label for the Base product type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $base_product_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\store\Entity\BaseProductType::load',
      ],
      '#disabled' => !$base_product_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $base_product_type = $this->entity;
    $status = $base_product_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Base product type.', [
          '%label' => $base_product_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Base product type.', [
          '%label' => $base_product_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($base_product_type->urlInfo('collection'));
  }

}
