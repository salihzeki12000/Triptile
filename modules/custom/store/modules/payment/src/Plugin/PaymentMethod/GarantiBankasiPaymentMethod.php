<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class GarantiBankasiPaymentMethod
 *
 * @PaymentMethod(
 *   id = "garanti_bankasi",
 *   label = @Translation("Garanti Bankasi"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class GarantiBankasiPaymentMethod extends PaymentMethodBase implements PayseraPaymentMethodInterface {

  use PayseraPaymentMethodTrait;

  protected static $payseraPaymentName = 'tr_garantibankasi';

}
