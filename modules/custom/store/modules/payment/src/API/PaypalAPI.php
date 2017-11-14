<?php

namespace Drupal\payment\API;

use Drupal\Core\Url;
use Drupal\payment\Entity\TransactionInterface;
use Drupal\payment\Plugin\PaymentMethod\CreditCardPaymentMethod;

/**
 * Class PaypalAPI.
 *
 * @package Drupal\payment
 */
class PaypalAPI extends PaymentAPIBase {

  use CreditCardAPITrait;

  /**
   * API endpoints.
   */
  const API_SANDBOX_URL = 'https://api-3t.sandbox.paypal.com/nvp',
    API_LIVE_URL = 'https://api-3t.paypal.com/nvp';

  const CHECKOUT_SANDBOX_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr',
    CHECKOUT_LIVE_URL = 'https://www.paypal.com/cgi-bin/webscr';

  /**
   * Supported API version.
   */
  const VERSION = '204';

  /**
   * Response statuses.
   */
  const ACK_SUCCESS = 'Success',
    ACK_SUCCESS_WITH_WARNING = 'SuccessWithWarning',
    ACK_FAILURE = 'Failure',
    ACK_FAILURE_WITH_WARNING = 'FailureWithWarning';

  /**
   * Payment statuses.
   */
  const PAYMENTSTATUS_NONE = 'None',
    PAYMENTSTATUS_CANCELED_REVERSAL = 'Canceled-Reversal',
    PAYMENTSTATUS_COMPLETED = 'Completed',
    PAYMENTSTATUS_DENIED = 'Denied',
    PAYMENTSTATUS_EXPIRED = 'Expired',
    PAYMENTSTATUS_FAILED = 'Failed',
    PAYMENTSTATUS_IN_PROGRESS = 'In-Progress',
    PAYMENTSTATUS_PARTIALLY_REFUNDED = 'Partially-Refunded',
    PAYMENTSTATUS_PENDING = 'Pending',
    PAYMENTSTATUS_REFUNDED = 'Refunded',
    PAYMENTSTATUS_REVERSED = 'Reversed',
    PAYMENTSTATUS_PROCESSED = 'Processed',
    PAYMENTSTATUS_VOIDED = 'Voided',
    PAYMENTSTATUS_COMPLETED_FUNDS_HELD = 'Completed-Funds-Held';

  /**
   * Refund statuses.
   */
  const REFUNDSTATUS_NONE = 'None',
    REFUNDSTATUS_INSTANT = 'Instant',
    REFUNDSTATUS_DELAYED = 'Delayed';

  /**
   * @var array
   *
   * Mapping of internal card shortcuts to cards in Paypal API.
   */
  protected static $cards = [
    CreditCardPaymentMethod::CARD_TYPE_VISA => 'Visa',
    CreditCardPaymentMethod::CARD_TYPE_MASTERCARD => 'MasterCard',
    CreditCardPaymentMethod::CARD_TYPE_DISCOVER => 'Discover',
    CreditCardPaymentMethod::CARD_TYPE_AMERICAN_EXPRESS => 'Amex',
  ];

  /**
   * @var \Drupal\Core\Url
   */
  protected $returnUrl;

  /**
   * @var \Drupal\Core\Url
   */
  protected $cancelUrl;

  /**
   * @var string
   */
  protected $token;

  /**
   * @var string
   */
  protected $payer_id;

  /**
   * @var \Drupal\payment\Entity\Transaction
   */
  protected $originalTransaction;

  /**
   * Sets return url for EC.
   *
   * @param \Drupal\Core\Url $url
   * @return static
   */
  public function setReturnUrl(Url $url) {
    $this->returnUrl = $url;
    return $this;
  }

  /**
   * Sets cancel url for EC.
   *
   * @param \Drupal\Core\Url $url
   * @return static
   */
  public function setCancelUrl(Url $url) {
    $this->cancelUrl = $url;
    return $this;
  }

  /**
   * Sets EC token.
   *
   * @param string $token
   * @return static
   */
  public function setToken($token) {
    $this->token = $token;
    return $this;
  }

  /**
   * Sets payer ID from EC.
   *
   * @param string $payer_id
   * @return static
   */
  public function setPayerId($payer_id) {
    $this->payer_id = $payer_id;
    return $this;
  }

  /**
   * Sets the original transaction that will be refunded.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @return $this
   */
  public function setOriginalTransaction (TransactionInterface $transaction) {
    $this->originalTransaction = $transaction;
    return $this;
  }

  /**
   * Makes DoDirectPayment API request.
   *
   * @return array
   * @throws \Drupal\payment\API\PaypalAPIException
   */
  public function doDirectPayment() {
    if (!isset($this->creditCardData)) {
      throw new PaypalAPIException('Credit card data is not provided for DoDirectPayment request.');
    }
    if (!isset($this->billingProfile)) {
      throw new PaypalAPIException('Billing profile is not provided for DoDirectPayment request.');
    }
    if (!isset($this->transaction)) {
      throw new PaypalAPIException('Transaction object is not provided for DoDirectPayment request.');
    }

    if ($this->transaction->isNew()) {
      $this->transaction->save();
    }

    $street = $this->billingProfile->getAddress()->getAddressLine1();
    if (empty($street) && $this->config['fake_billing_address']) {
      $street = 'Main street, 1';
    }
    $city = $this->billingProfile->getAddress()->getLocality();
    if (empty($city) && $this->config['fake_billing_address']) {
      $geoip = \Drupal::service('master.maxmind')->getInfoByIp($this->getClientIP());
      $city = isset($geoip['city']) ? $geoip['city'] : 'Capital';
    }
    $state = $this->billingProfile->getAddress()->getAdministrativeArea();
    if (empty($state) && $this->config['fake_billing_address']) {
      $state = 'State';
    }
    $zip = $this->billingProfile->getAddress()->getPostalCode();
    if (empty($state) && $this->config['fake_billing_address']) {
      $zip = '321456';
    }

    // @todo Add support of configured soft descriptor
    $params = [
      'METHOD' => 'DoDirectPayment',
      'PAYMENTACTION' => 'Sale',
      'IPADDRESS' => $this->getClientIP(),
      'RETURNFMFDETAILS' => 1,

      'CREDITCARDTYPE' => static::$cards[$this->creditCardData['card_type']],
      'ACCT' => $this->creditCardData['card_number'],
      'EXPDATE' => $this->creditCardData['card_expiration_date']['dates']['month'] . $this->creditCardData['card_expiration_date']['dates']['year'],
      'CVV2' => $this->creditCardData['card_code'],

      'EMAIL' => $this->billingProfile->getEmail(),
      'FIRSTNAME' => $this->billingProfile->getAddress()->getGivenName(),
      'LASTNAME' => $this->billingProfile->getAddress()->getFamilyName(),

      'STREET' => $street,
      'CITY' => $city,
      'STATE' => $state,
      'COUNTRYCODE' => $this->billingProfile->getAddress()->getCountryCode(),
      'ZIP' => $zip,

      'AMT' => $this->transaction->getAmount()->getNumber(),
      'CURRENCYCODE' => $this->transaction->getAmount()->getCurrencyCode(),
      'DESC' => substr($this->transaction->getInvoice()->getDescription(), 0, 127),
      'INVNUM' => $this->getExternalId(),
      //'NOTIFYURL' => Url::fromRoute('payment.paypal_ipn_url', ['transaction' => $this->transaction->id()])->setAbsolute()->toString(),
    ];

    if (!empty($this->config['soft_descriptor'])) {
      $params['SOFTDESCRIPTOR'] = $this->config['soft_descriptor'];
    }

    $log_params = $params;
    $log_params['ACCT'] = substr($log_params['ACCT'], strlen($log_params['ACCT']) - 4);
    $this->transaction->appendLog($log_params);
    $response = $this->apiRequest($params);
    $this->transaction->appendLog($response);
    return $response;
  }

  /**
   * Makes GetTransactionDetails API request to load transaction from Paypal.
   *
   * @return array
   * @throws \Drupal\payment\API\PaypalAPIException
   */
  public function getRemoteTransaction() {
    if (!isset($this->transaction)) {
      throw new PaypalAPIException('Transaction object is not provided for DoDirectPayment request.');
    }

    $params = [
      'METHOD' => 'GetTransactionDetails',
      'TRANSACTIONID' => $this->transaction->getRemoteId(),
    ];

    $this->transaction->appendLog($params);
    $response = $this->apiRequest($params);
    $this->transaction->appendLog($response);
    return $response;
  }

  /**
   * Makes SetExpressCheckout API request.
   *
   * @return array
   * @throws \Drupal\payment\API\PaypalAPIException
   */
  public function setExpressCheckout() {
    if (!isset($this->transaction)) {
      throw new PaypalAPIException('Transaction object is not provided for SetExpressCheckout request.');
    }
    if (!isset($this->returnUrl)) {
      throw new PaypalAPIException('Return URL is not provided for SetExpressCheckout request.');
    }
    if (!isset($this->cancelUrl)) {
      throw new PaypalAPIException('Cancel URL is not provided for SetExpressCheckout request.');
    }

    $params = [
      'METHOD' => 'SetExpressCheckout',
      'RETURNURL' => $this->returnUrl->setAbsolute()->toString(),
      'CANCELURL' => $this->cancelUrl->setAbsolute()->toString(),
      'NOSHIPPING' => 1,
      'ALLOWNOTE' => 0,
      'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
      'PAYMENTREQUEST_0_AMT' => $this->transaction->getAmount()->getNumber(),
      'PAYMENTREQUEST_0_CURRENCYCODE' => $this->transaction->getAmount()->getCurrencyCode(),
      'PAYMENTREQUEST_0_INVNUM' => $this->transaction->getInvoice()->getInvoiceNumber(),
      'PAYMENTREQUEST_0_DESC' => $this->transaction->getInvoice()->getDescription(),
    ];

    $this->transaction->appendLog($params);
    $response = $this->apiRequest($params);
    $this->transaction->appendLog($response);
    return $response;
  }

  /**
   * Makes GetExpressCheckoutDetails API request to load EC payment details.
   *
   * @return array
   * @throws \Drupal\payment\API\PaypalAPIException
   */
  public function getExpressCheckoutDetails() {
    if (!isset($this->token)) {
      throw new PaypalAPIException('Token is not provided for GetExpressCheckoutDetails request.');
    }
    if (!isset($this->transaction)) {
      throw new PaypalAPIException('Transaction object is not provided for GetExpressCheckoutDetails request.');
    }

    $params = [
      'METHOD' => 'GetExpressCheckoutDetails',
      'TOKEN' => $this->token,
    ];

    $this->transaction->appendLog($params);
    $response = $this->apiRequest($params);
    $this->transaction->appendLog($response);
    return $response;
  }

  /**
   * Makes DoExpressCheckoutPayment API request.
   *
   * @return array
   * @throws \Drupal\payment\API\PaypalAPIException
   */
  public function doExpressCheckoutPayment() {
    if (!isset($this->token)) {
      throw new PaypalAPIException('Token is not provided for DoExpressCheckoutPayment request.');
    }
    if (!isset($this->transaction)) {
      throw new PaypalAPIException('Transaction object is not provided for DoExpressCheckoutPayment request.');
    }
    if (!isset($this->payer_id)) {
      throw new PaypalAPIException('Payer ID is not provided for DoExpressCheckoutPayment request.');
    }

    // @todo Add support of configured soft descriptor
    $params = [
      'METHOD' => 'DoExpressCheckoutPayment',
      'TOKEN' => $this->token,
      'PAYERID' => $this->payer_id,
      'RETURNFMFDETAILS' => 1,
      'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
      'PAYMENTREQUEST_0_AMT' => $this->transaction->getAmount()->getNumber(),
      'PAYMENTREQUEST_0_CURRENCYCODE' => $this->transaction->getAmount()->getCurrencyCode(),
      'PAYMENTREQUEST_0_INVNUM' => $this->getExternalId(),
      'PAYMENTREQUEST_0_DESC' => $this->transaction->getInvoice()->getDescription(),
      //'PAYMENTREQUEST_0_NOTIFYURL' => Url::fromRoute('payment.paypal_ipn_url', ['transaction' => $this->transaction->id()])->setAbsolute()->toString(),
    ];

    $this->transaction->appendLog($params);
    $response = $this->apiRequest($params);
    $this->transaction->appendLog($response);
    return $response;
  }

  /**
   * Generates EC page url.
   *
   * @param string $token
   * @return \Drupal\Core\Url
   */
  public function getCheckoutPageUrl($token) {
    return Url::fromUri($this->getUrl('checkout'), [
      'query' => [
        'cmd' => '_express-checkout',
        'token' => $token,
      ],
    ]);
  }

  /**
   * Does refund of a transaction.
   *
   * @return array
   * @throws \Drupal\payment\API\PaypalAPIException
   */
  public function refundTransaction() {
    if (!isset($this->transaction)) {
      throw new PaypalAPIException('Refund transaction object is not provided for RefundTransaction request.');
    }
    if (!isset($this->originalTransaction)) {
      throw new PaypalAPIException('Original transaction object is not provided for RefundTransaction request.');
    }

    $refundAmount = $this->transaction->getAmount()->multiply(-1);

    $params = [
      'METHOD' => 'RefundTransaction',
      'TRANSACTIONID' => $this->originalTransaction->getRemoteId(),
      'INVOICEID' => $this->transaction->getInvoice()->getInvoiceNumber(),
      'REFUNDTYPE' => $refundAmount->equals($this->originalTransaction->getAmount()) ? 'Full' : 'Partial',

    ];

    if (!$refundAmount->equals($this->originalTransaction->getAmount())) {
      $params['AMT'] = $refundAmount->getNumber();
    }

    $this->transaction->appendLog($params);
    $response = $this->apiRequest($params);
    $this->transaction->appendLog($response);
    return $response;
  }

  /**
   * Performs API request to Paypal gateway,
   *
   * @param array $params
   * @return array
   * @throws \Drupal\payment\API\PaypalAPIException
   */
  protected function apiRequest(array $params) {
    $url = $this->getUrl('api');
    $params = array_merge($this->defaultParams(), $params);
    $body = $this->generateRequestBody($params);
    try {
      $response = $this->httpClient->post($url, ['body' => $body]);
    }
    catch (\Exception $e) {
      throw new PaypalAPIException('Error occurred during the HTTP request to API endpoint: ' . $e->getMessage());
    }

    return $this->parseResponseBody($response->getBody());
  }

  /**
   * Gets endpoint url according to current configuration and $type.
   *
   * @param string $type
   * @return null|string
   * @throws \Drupal\payment\API\PaypalAPIException
   */
  protected function getUrl($type) {
    if (!isset($this->config['sandbox_mode'])) {
      throw new PaypalAPIException('Invalid configurations provided.');
    }

    switch ($type) {
      case 'api':
        return $this->config['sandbox_mode'] ? static ::API_SANDBOX_URL : static ::API_LIVE_URL;
      case 'checkout':
        return $this->config['sandbox_mode'] ? static ::CHECKOUT_SANDBOX_URL : static ::CHECKOUT_LIVE_URL;
    }

    return null;
  }

  /**
   * Gets default params for key-value pairs.
   *
   * @return array
   * @throws \Drupal\payment\API\PaypalAPIException
   */
  protected function defaultParams() {
    if (!isset($this->config['username'], $this->config['password'], $this->config['signature'])) {
      throw new PaypalAPIException('Invalid configurations provided.');
    }

    return [
      'USER' => $this->config['username'],
      'PWD' => $this->config['password'],
      'SIGNATURE' => $this->config['signature'],
      'VERSION' => static::VERSION,
    ];
  }

  /**
   * Merges params into a string according to Paypal API specs.
   *
   * @param array $params
   * @return string
   */
  protected function generateRequestBody(array $params) {
    $pairs = [];
    foreach ($params as $key => $value) {
      $pairs[] = $key . '=' . urlencode($value);
    }
    return implode('&', $pairs);
  }

  /**
   * Converts response from Paypal into array.
   *
   * @param string $body
   * @return array
   */
  protected function parseResponseBody($body) {
    $pairs = explode('&', $body);
    $result = [];
    foreach ($pairs as $pair) {
      list($key, $value) = explode('=', $pair);
      $result[urldecode($key)] = urldecode($value);
    }

    return $result;
  }

  /**
   * Gets client ip from transaction or request.
   *
   * @return string
   */
  protected function getClientIP() {
    if ($this->transaction && $this->transaction->getIPAddress()) {
      return $this->transaction->getIPAddress();
    }
    return $this->request->getClientIp();
  }

}
