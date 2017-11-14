<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class AkbankPaymentMethod
 *
 * @PaymentMethod(
 *   id = "akbank",
 *   label = @Translation("Akbank"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class AkbankPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'tr_akbank';

}
