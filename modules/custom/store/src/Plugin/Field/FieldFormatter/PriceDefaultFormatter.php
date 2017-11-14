<?php

namespace Drupal\store\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;


/**
 * Plugin implementation of the 'price_default' formatter.
 *
 * @FieldFormatter(
 *   id = "price_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "price"
 *   }
 * )
 */
class PriceDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'strip_trailing_zeroes' => FALSE,
      'display_currency_code' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $elements['strip_trailing_zeroes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip trailing zeroes after the decimal point.'),
      '#default_value' => $this->getSetting('strip_trailing_zeroes'),
    ];
    $elements['display_currency_code'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display the currency code instead of the currency symbol.'),
      '#default_value' => $this->getSetting('display_currency_code'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $item->toPrice(),
      ];
    }

    return $elements;
  }

}
