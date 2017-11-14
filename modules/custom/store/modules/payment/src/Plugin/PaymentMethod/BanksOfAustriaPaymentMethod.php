<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class BanksOfAustriaPaymentMethod
 *
 * @PaymentMethod(
 *   id = "at_banks",
 *   label = @Translation("Banks of Austria"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class BanksOfAustriaPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'directebat';

  protected static $billingCountry = 'AT';

}
