<?php

namespace Drupal\store\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'price_default' widget.
 *
 * @FieldWidget(
 *   id = "price_default",
 *   label = @Translation("Price"),
 *   field_types = {
 *     "price"
 *   }
 * )
 */
class PriceDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $currency_options = \Drupal::service('currency.form_helper')->getCurrencyOptions();

    // @todo will delete on production.
    unset($currency_options['XXX']);
    $element['number'] = [
      '#type' => 'number',
      '#title' => $element['#title'],
      '#required' => $element['#required'],
    ];
    $element['currency_code'] = [
      '#title' => $this->t('Price currency'),
      '#title_display' => 'none',
      '#empty_value' => '',
      '#options' => $currency_options,
      '#type' => 'select',
      '#required' => $element['#required'],
    ];
    if (!$items[$delta]->isEmpty()) {
      $element['number']['#default_value'] = $items[$delta]->toPrice()->getNumber();
      $element['currency_code']['#default_value'] = $items[$delta]->toPrice()->getCurrencyCode();
    }

    return $element;
  }

}
