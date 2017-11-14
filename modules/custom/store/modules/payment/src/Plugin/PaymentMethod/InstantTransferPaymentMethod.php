<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class InstantTransferPaymentMethod
 *
 * @PaymentMethod(
 *   id = "instant_transfer",
 *   label = @Translation("Instant Transfer"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class InstantTransferPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'ppro_instant_transfer';

}
