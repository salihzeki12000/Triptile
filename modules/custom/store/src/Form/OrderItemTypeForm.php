<?php

namespace Drupal\store\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OrderItemTypeForm.
 *
 * @package Drupal\store\Form
 */
class OrderItemTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $order_item_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $order_item_type->label(),
      '#description' => $this->t("Label for the Order item type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $order_item_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\store\Entity\OrderItemType::load',
      ],
      '#disabled' => !$order_item_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $order_item_type = $this->entity;
    $status = $order_item_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Order item type.', [
          '%label' => $order_item_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Order item type.', [
          '%label' => $order_item_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($order_item_type->urlInfo('collection'));
  }

}
