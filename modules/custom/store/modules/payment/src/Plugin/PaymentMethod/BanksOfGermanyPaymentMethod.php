<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class BanksOfGermanyPaymentMethod
 *
 * @PaymentMethod(
 *   id = "de_banks",
 *   label = @Translation("Banks of Germany"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class BanksOfGermanyPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'directeb';

}
