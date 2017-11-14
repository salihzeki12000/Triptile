<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class CashuPaymentMethod
 *
 * @PaymentMethod(
 *   id = "cashu",
 *   label = @Translation("Cashu"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class CashuPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'cashu';

}
