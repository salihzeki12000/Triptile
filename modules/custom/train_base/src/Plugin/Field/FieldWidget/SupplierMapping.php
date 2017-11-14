<?php

namespace Drupal\train_base\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * Plugin implementation of the 'supplier_mapping' widget.
 *
 * @FieldWidget(
 *   id = "supplier_mapping",
 *   label = @Translation("Supplier mapping"),
 *   field_types = {
 *     "supplier_mapping"
 *   }
 * )
 */
class SupplierMapping extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = parent::formElement($items, $delta, $element, $form, $form_state);

    $widget['target_id']['#title'] = $this->t('Supplier');
    $widget['target_id']['#title_display'] = 'before';
    $widget['code'] = array(
      '#title' => $this->t('Code'),
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]) ? $items[$delta]->code : NULL,
    );
    $widget['description'] = array(
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => isset($items[$delta]) ? $items[$delta]->description : NULL,
    );

    return $widget;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['show_description'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show description'),
      '#default_value' => TRUE,
    );
    return $element;
  }
}
