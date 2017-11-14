<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class TrustPayTurkeyPaymentMethod
 *
 * @PaymentMethod(
 *   id = "tr_trustpay",
 *   label = @Translation("TrustPay (Turkey)"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class TrustPayTurkeyPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'tr_trustpay';

}
