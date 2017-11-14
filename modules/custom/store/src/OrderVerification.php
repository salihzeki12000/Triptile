<?php

namespace Drupal\store;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\master\MasterMaxMind;
use Drupal\payment\Entity\Transaction;
use Drupal\store\Entity\StoreOrderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class OrderVerification
 *
 * @package Drupal\store
 */
class OrderVerification {

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\master\MasterMaxMind
   */
  protected $maxmind;

  /**
   * @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage
   */
  protected $expressionLanguage;

  /**
   * OrderVerification constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   * @param \Drupal\master\MasterMaxMind $maxmind
   * @param \Symfony\Component\ExpressionLanguage\ExpressionLanguage $expression_language
   */
  public function __construct(ConfigFactory $config, MasterMaxMind $maxmind, ExpressionLanguage $expression_language) {
    $this->config = $config->get('store.settings');
    $this->maxmind = $maxmind;
    $this->expressionLanguage = $expression_language;
  }

  /**
   * Checks if order should be verified.
   *
   * @param \Drupal\store\Entity\StoreOrderInterface $order
   * @return string
   */
  public function doSetForManualVerification(StoreOrderInterface $order) {
    if ($condition = $this->config->get('order_verification_condition')) {
      // First prepare variables for condition expression.
      $vars = [
        'currency' => $order->getOrderTotal()->getCurrencyCode(),
        'language' => $order->language()->getId(),
        'billing_country' => NULL,
        'email' => NULL,
        'ip_country' => NULL,
        'merchant_id' => NULL,
        'failed_transactions' => NULL,
        'depth' => NULL,
        'suppliers' => [],
      ];

      $hasSuccessTransaction = FALSE;
      $countOfFailedTransactions = 0;
      foreach ($order->getInvoices() as $invoice) {
        if ($customerProfile = $invoice->getCustomerProfile()) {
          $vars['billing_country'] = $customerProfile->getAddress()
            ->getCountryCode();
          $vars['email'] = $customerProfile->getEmail();
        }

        foreach ($invoice->getTransactions() as $transaction) {
          if ($transaction->getType() == Transaction::TYPE_PAYMENT) {
            if (!$hasSuccessTransaction) {
              $vars['ip_country'] = $this->maxmind->getCountry($transaction->getIPAddress());
            }
            if ($transaction->getStatus() == Transaction::STATUS_SUCCESS) {
              $hasSuccessTransaction = TRUE;
              $vars['merchant_id'] = $transaction->getMerchant()
                ->getMerchantId();
            }
            elseif ($transaction->getStatus() == Transaction::STATUS_FAILED) {
              $countOfFailedTransactions++;
            }
          }
        }
      }
      $vars['failed_transactions'] = $countOfFailedTransactions;

      foreach ($order->getTickets() as $ticket) {
        if (!$vars['depth'] && $ticket->getLegNumber() == 1) {
          $vars['depth'] = $ticket->getDepartureDateTime()
            ->diff(DrupalDateTime::createFromTimestamp($order->getCreatedTime()))->days;
        }

        $supplierCode = $ticket->getCoachClass()->getSupplier()->getCode();
        if (!in_array($supplierCode, $vars['suppliers'])) {
          $vars['suppliers'][] = $supplierCode;
        }
      }

      return $this->expressionLanguage->evaluate($condition, $vars);
    }

    return false;
  }

}
