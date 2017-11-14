<?php

namespace Drupal\payment\API;

use Drupal\Core\Url;
use Drupal\payment\Entity\TransactionInterface;

class TransactProAPI extends PaymentAPIBase {

  use CreditCardAPITrait;

  /**
   * API endpoints.
   */
  const LIVE_URL = 'https://www2.1stpayments.net/gwprocessor2.php?a=%METHOD%',
    SANDBOX_URL = 'https://gw2sandbox.tpro.lv:8443/gw2test/gwprocessor2.php?a=%METHOD%';

  /**
   * Response parameters separator.
   */
  const RESPONSE_PARAMETER_SEPARATOR = '~';

  /**
   * Response parameter name and value separator.
   */
  const RESPONSE_KEYVALUE_SEPARATOR = ':';

  /**
   * f_extended parameter value to get:
   * - processor result code (ResultCode)
   */
  const EXTENDED_RESPONSE_VALUE_1 = 1;

  /**
   * f_extended parameter value to get:
   * - processor result code (ResultCode)
   * - processor approval code (ApprovalCode)
   */
  const EXTENDED_RESPONSE_VALUE_2 = 2;

  /**
   * f_extended parameter value to get:
   * - processor result code string (ResultCodeString)
   */
  const EXTENDED_RESPONSE_VALUE_3 = 3;

  /**
   * f_extended parameter value to get:
   * - processor result code (ResultCode)
   * - processor approval code (ApprovalCode)
   * - card issuer country (CardIssuerCountry)
   */
  const EXTENDED_RESPONSE_VALUE_4 = 4;

  /**
   * f_extended parameter value to get:
   * - processor result code (ResultCode)
   * - processor approval code (ApprovalCode)
   * - name on card (NameOnCard)
   * - card masked (CardMasked)
   */
  const EXTENDED_RESPONSE_VALUE_5 = 5;

  /**
   * f_extended parameter value to get:
   * - processor result code (ResultCode)
   * - processor approval code (ApprovalCode)
   * - transaction creation time (dt_created)
   * - 3d status, 1-not 3D, 2–3D, 0–not defined (3d)
   */
  const EXTENDED_RESPONSE_VALUE_6 = 6;

  /**
   * f_extended parameter value to get:
   * - processor result code (ResultCode)
   * - processor approval code (ApprovalCode)
   * - card issuer country (CardIssuerCountry)
   * - name on card (NameOnCard)
   * - card masked (CardMasked)
   * - 3d status, 1-not 3D, 2–3D, 0–not defined (3d)
   */
  const EXTENDED_RESPONSE_VALUE_7 = 7;

  /**
   * f_extended parameter value to get:
   * - processor result code (ResultCode)
   * - extended error code (ExtendedErrorCode)
   */
  const EXTENDED_RESPONSE_VALUE_8 = 8;

  /**
   * @var \Drupal\payment\Entity\Transaction
   */
  protected $originalTransaction;

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
   * Makes initialization request.
   *
   * @return array
   * @throws \Drupal\payment\API\TransactProApiException
   */
  public function init() {
    if (!isset($this->transaction)) {
      throw new TransactProApiException('Transaction object is not provided for initialization request.');
    }
    if (!isset($this->config)) {
      throw new TransactProApiException('Configuration is not provided.');
    }
    if (!isset($this->billingProfile)) {
      throw new TransactProApiException('Billing profile is not provided for initialization request.');
    }
    if (!isset($this->creditCardData)) {
      throw new TransactProApiException('Credit card data is not provided for initialization request.');
    }

    if ($this->transaction->isNew()) {
      $this->transaction->save();
    }

    $params = [
      'guid' => $this->config['guid'],
      'pwd' => sha1($this->config['password']),
      'rs' => $this->config['routing_string'],
      'merchant_transaction_id' => $this->getExternalId(),
      'user_ip' => $this->request->getClientIp(),
      'description' => $this->transaction->getInvoice()->getDescription(),
      'amount' => $this->transaction->getAmount()->getNumber() * 100,
      'currency' => $this->transaction->getAmount()->getCurrencyCode(),
      'street' => $this->billingProfile->getAddress()->getAddressLine1(),
      'city' => $this->billingProfile->getAddress()->getLocality(),
      'country' => $this->billingProfile->getAddress()->getCountryCode(),
      'state' => $this->billingProfile->getAddress()->getAdministrativeArea() ?: 'NA',
      'email' => $this->billingProfile->getEmail(),
      'phone' => $this->billingProfile->getPhoneNumber(),
      'merchant_site_url' => Url::fromRoute('<front>')->setAbsolute()->toString(),
      'name_on_card' => $this->creditCardData['card_owner'],
      'card_bin' => substr($this->creditCardData['card_number'], 0, 6),
    ];

    $logParams = $params;
    unset($logParams['guid'], $logParams['pwd'], $logParams['rs']);
    $this->transaction->appendLog($logParams);
    $response = $this->apiRequest($params, 'init');
    $this->transaction->appendLog($response);
    return $response;

  }

  /**
   * Makes charge request.
   *
   * @return array
   * @throws \Drupal\payment\API\TransactProApiException
   */
  public function charge() {
    if (!isset($this->transaction)) {
      throw new TransactProApiException('Transaction object is not provided for charge request.');
    }
    if (!isset($this->config)) {
      throw new TransactProApiException('Configuration is not provided.');
    }
    if (!isset($this->creditCardData)) {
      throw new TransactProApiException('Credit card data is not provided for charge request.');
    }

    $params = [
      'f_extended' => static::EXTENDED_RESPONSE_VALUE_5,
      'init_transaction_id' => $this->transaction->getRemoteId(),
      'cc' => $this->creditCardData['card_number'],
      'cvv' => $this->creditCardData['card_code'],
      'expire' => str_pad($this->creditCardData['card_expiration_date']['dates']['month'], 2, 0, STR_PAD_LEFT)
        . '/' . substr($this->creditCardData['card_expiration_date']['dates']['year'], -2)
    ];

    $logParams = $params;
    $logParams['cc'] = substr($logParams['cc'], strlen($logParams['cc']) - 4);
    $this->transaction->appendLog($logParams);
    $response = $this->apiRequest($params, 'charge');
    $this->transaction->appendLog($response);
    return $response;
  }

  /**
   * Makes status request.
   *
   * @return array
   * @throws \Drupal\payment\API\TransactProApiException
   */
  public function statusRequest() {
    if (!isset($this->transaction)) {
      throw new TransactProApiException('Transaction object is not provided for charge request.');
    }
    if (!isset($this->config)) {
      throw new TransactProApiException('Configuration is not provided.');
    }

    $params = [
      'guid' => $this->config['guid'],
      'pwd' => sha1($this->config['password']),
      'request_type' => 'transaction_status',
      'f_extended' => static::EXTENDED_RESPONSE_VALUE_5,
      'init_transaction_id' => $this->transaction->getRemoteId(),
    ];

    $logParams = $params;
    unset($logParams['guid'], $logParams['pwd']);
    $this->transaction->appendLog($logParams);
    $response = $this->apiRequest($params, 'status_request');
    $this->transaction->appendLog($response);
    return $response;
  }

  /**
   * @return array
   * @throws \Drupal\payment\API\TransactProApiException
   */
  public function refund() {
    if (!isset($this->transaction)) {
      throw new TransactProApiException('Refund transaction object is not provided for refund request.');
    }
    if (!isset($this->originalTransaction)) {
      throw new TransactProApiException('Original transaction object is not provided for refund request.');
    }
    if (!isset($this->config)) {
      throw new TransactProApiException('Configuration is not provided.');
    }

    $params = [
      'account_guid' => $this->config['guid'],
      'pwd' => sha1($this->config['password']),
      'init_transaction_id' => $this->originalTransaction->getRemoteId(),
      'amount_to_refund' => $this->transaction->getAmount()->multiply(-1)->getNumber() * 100,
      'merchant_transaction_id' => $this->getExternalId(),
      'details' => 'true',
    ];

    $logParams = $params;
    unset($logParams['account_guid'], $logParams['pwd']);
    $this->transaction->appendLog($logParams);
    $response = $this->apiRequest($params, 'refund');
    $this->transaction->appendLog($response);
    return $response;
  }

  /**
   * Makes a request to API endpoint
   *
   * @param array $params
   * @param string $method
   * @return array
   * @throws \Drupal\payment\API\TransactProApiException
   */
  protected function apiRequest(array $params, $method) {
    $url = $this->config['sandbox_mode'] ? static::SANDBOX_URL : static::LIVE_URL;
    $url = str_replace('%METHOD%', $method, $url);
    $options = ['form_params' => $params];

    if ($this->config['sandbox_mode'] && !empty($this->config['proxy'])) {
      $options['proxy'] = $this->config['proxy'];
      $options['verify'] = false;
    }

    try {
      $response = $this->httpClient->post($url, $options);
    }
    catch (\Exception $e) {
      throw new TransactProApiException('Error occurred during the HTTP request to API endpoint: ' . $e->getMessage());
    }

    return $this->parseResponseBody($response->getBody());
  }

  /**
   * Parses responses from the Transact Pro system.
   *
   * @param string $body
   * @return array
   */
  protected function parseResponseBody($body) {
    $response = [];
    foreach (explode(static::RESPONSE_PARAMETER_SEPARATOR, $body) as $part) {
      $subParts = explode(static::RESPONSE_KEYVALUE_SEPARATOR, $part);
      $propertyName = array_shift($subParts);
      $response[$propertyName] = implode(static::RESPONSE_KEYVALUE_SEPARATOR, $subParts);
    }
    return $response;
  }

}
