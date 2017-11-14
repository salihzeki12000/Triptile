<?php

namespace Drupal\payment\API;

use Drupal\payment\Entity\TransactionInterface;
use Drupal\store\Entity\CustomerProfile;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;

abstract class PaymentAPIBase {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @var array
   */
  protected $config;

  /**
   * @var \Drupal\payment\Entity\Transaction
   */
  protected $transaction;

  /**
   * @var \Drupal\store\Entity\CustomerProfile
   */
  protected $billingProfile;

  /**
   * EcommpayAPI constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  public function __construct(Client $http_client, Request $request) {
    $this->httpClient = $http_client;
    $this->request = $request;
  }

  /**
   * Sets configs.
   *
   * @param array $config
   * @return static
   */
  public function setConfig(array $config) {
    $this->config = $config;
    return $this;
  }

  /**
   * Sets the transaction that will be processed.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return static
   */
  public function setTransaction(TransactionInterface $transaction) {
    $this->transaction = $transaction;
    return $this;
  }

  /**
   * Sets the customer profile.
   *
   * @param \Drupal\store\Entity\CustomerProfile $customer_profile
   * @return static
   */
  public function setBillingProfile(CustomerProfile $customer_profile) {
    $this->billingProfile = $customer_profile;
    return $this;
  }

  /**
   * Gets id that will be used as external id of an order in Ecommpay.
   *
   * @param \Drupal\payment\Entity\TransactionInterface|null $transaction
   * @return null|string
   */
  protected function getExternalId(TransactionInterface $transaction = null) {
    $id = null;
    $transaction = $transaction ?? $this->transaction;
    if (isset($transaction)) {
      $id = $transaction->getInvoice()->getOrder() ? $transaction->getInvoice()->getOrder()->getOrderNumber() : $transaction->getInvoice()->getInvoiceNumber();
      $id .= '_' . $transaction->id();
    }

    return $id;
  }

}
