<?php

namespace Drupal\payment\API;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;

class PaymentAPIFactory {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @internal param \Symfony\Component\HttpFoundation\Request $request
   */
  public function __construct(Client $http_client, RequestStack $request_stack) {
    $this->httpClient = $http_client;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Creates an instance of API provider.
   *
   * @param $payment_system
   * @return \Drupal\payment\API\PaymentAPIBase
   */
  public function get($payment_system) {
    switch ($payment_system) {
      case 'paypal':
        return new PaypalAPI($this->httpClient, $this->request);
      case 'ecommpay':
        return new EcommpayAPI($this->httpClient, $this->request);
      case 'paysera':
        return new PayseraAPI($this->httpClient, $this->request);
      case 'transact_pro':
        return new TransactProAPI($this->httpClient, $this->request);
    }

    return null;
  }

}
