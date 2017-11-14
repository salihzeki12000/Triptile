<?php

namespace Drupal\payment\Plugin\PaymentMethod;

/**
 * Class Paypal
 * @PaymentMethod(
 *   id = "paypal",
 *   label = @Translation("Paypal"),
 *   operations_provider = "\Drupal\payment\Plugin\PaymentMethod\PluginOperationsProvider"
 * )
 */
class PaypalPaymentMethod extends PaymentMethodBase {}
