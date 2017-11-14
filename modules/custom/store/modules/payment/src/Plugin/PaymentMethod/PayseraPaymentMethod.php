<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class PayseraPaymentMethod
 *
 * @PaymentMethod(
 *   id = "paysera_wallet",
 *   label = @Translation("Paysera"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class PayseraPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'wallet';

  protected static $billingCountry = 'LT';

}
