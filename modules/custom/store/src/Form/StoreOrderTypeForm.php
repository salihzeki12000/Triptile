<?php

namespace Drupal\store\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StoreOrderTypeForm.
 *
 * @package Drupal\store\Form
 */
class StoreOrderTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $store_order_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $store_order_type->label(),
      '#description' => $this->t("Label for the Store order type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $store_order_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\store\Entity\StoreOrderType::load',
      ],
      '#disabled' => !$store_order_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $store_order_type = $this->entity;
    $status = $store_order_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Store order type.', [
          '%label' => $store_order_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Store order type.', [
          '%label' => $store_order_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($store_order_type->urlInfo('collection'));
  }

}
