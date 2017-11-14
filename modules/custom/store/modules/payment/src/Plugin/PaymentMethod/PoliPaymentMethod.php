<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class PoliPaymentMethod
 *
 * @PaymentMethod(
 *   id = "poli",
 *   label = @Translation("Poli"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class PoliPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'ppro_poli';

}
