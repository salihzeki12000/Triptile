<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class BanksOfNetherlandsPaymentMethod
 *
 * @PaymentMethod(
 *   id = "nl_banks",
 *   label = @Translation("Banks of Netherlands"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class BanksOfNetherlandsPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'directebnl';

  protected static $billingCountry = 'NL';

}
