<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class GiropayPaymentMethod
 *
 * @PaymentMethod(
 *   id = "giropay",
 *   label = @Translation("Giropay"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class GiropayPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'giro_pay';

  protected static $billingCountry = 'DE';

}
