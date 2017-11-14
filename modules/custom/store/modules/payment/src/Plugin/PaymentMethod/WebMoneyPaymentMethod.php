<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class WebMoneyPaymentMethod
 *
 * @PaymentMethod(
 *   id = "webmoney",
 *   label = @Translation("WebMoney"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class WebMoneyPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'webmoney';

}
