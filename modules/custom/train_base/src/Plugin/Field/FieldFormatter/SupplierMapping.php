<?php

namespace Drupal\train_base\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'supplier_mapping' formatter.
 *
 * @FieldFormatter(
 *   id = "supplier_mapping",
 *   label = @Translation("Supplier mapping"),
 *   field_types = {
 *     "supplier_mapping"
 *   }
 * )
 */
class SupplierMapping extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $values = $items->getValue();

    foreach ($elements as $delta => $entity) {
      $elements[$delta]['#suffix'] = ' Code: ' .  $values[$delta]['code'] . ' Description: ' .  $values[$delta]['description'];
    }

    return $elements;
  }

}
