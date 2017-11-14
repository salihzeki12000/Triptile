<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\payment\Entity\TransactionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for Payment adapter plugins.
 */
interface PaymentAdapterInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Checks if the payment adapter is configured to use sandbox to process payments.
   *
   * @return bool
   */
  public function isInSandboxMode();

  /**
   * Gets the default currency used by the payment adapter.
   *
   * @return string
   */
  public function getDefaultCurrency();


  /**
   * Gets the list of supported currencies.
   *
   * @return array
   */
  public function getSupportedCurrencies();

  /**
   * Checks the transaction status on remote gateway and updates transaction status.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return static
   */
  public function syncTransactionStatus(TransactionInterface $transaction);

  /**
   * Processes request from payment server.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return static
   */
  public function processTransactionUpdateRequest(TransactionInterface $transaction, Request $request);

}
