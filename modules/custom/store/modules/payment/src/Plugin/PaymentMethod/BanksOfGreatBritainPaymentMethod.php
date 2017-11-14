<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class BanksOfBelgiumPaymentMethod
 *
 * @PaymentMethod(
 *   id = "gb_banks",
 *   label = @Translation("Banks of Great Britain"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class BanksOfGreatBritainPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'directebgb';

}
