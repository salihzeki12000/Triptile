<?php

namespace Drupal\payment\Plugin\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class SafetyPayPaymentMethod
 *
 * @PaymentMethod(
 *   id = "safetypay",
 *   label = @Translation("SafetyPay"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class SafetyPayPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait {
    buildBillingProfileForm as buildBillingProfileFormTrait;
  }

  protected static $payseraPaymentName = 'safetypay';

  protected static $billingCountry = 'DE';

  public function buildBillingProfileForm(array $form, FormStateInterface $form_state, $include_title = FALSE) {
    $form = $this->buildBillingProfileFormTrait($form, $form_state, $include_title);

    if ($countryCode = \Drupal::service('master.maxmind')->getCountry()) {
      $form['country_code']['#value'] = $countryCode;
    }
    return $form;
  }

}
