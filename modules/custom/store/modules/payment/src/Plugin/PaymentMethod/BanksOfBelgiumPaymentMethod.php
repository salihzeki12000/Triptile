<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class BanksOfBelgiumPaymentMethod
 *
 * @PaymentMethod(
 *   id = "be_banks",
 *   label = @Translation("Banks of Belgium"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class BanksOfBelgiumPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'directebbe';

}
