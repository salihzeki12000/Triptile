<?php

namespace Drupal\payment\Plugin\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class Invoice
 * @PaymentMethod(
 *   id = "invoice",
 *   label = @Translation("Invoice"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class InvoicePaymentMethod extends PaymentMethodBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaymentDataForm(array $form, FormStateInterface $form_state, $include_title = FALSE) {
    $form['description'] = [
      '#markup' => '<div class="payment-method-description">' . $this->t('By using this payment method you can place the order in our system and pay offline for it later.', [], ['context' => 'Invoice payment method description']),
    ];

    return $form;
  }

}
