<?php

namespace Drupal\payment\API;

use Drupal\payment\Entity\Transaction;
use Drupal\payment\Entity\TransactionInterface;
use ForceUTF8\Encoding;

/**
 * Class EcommpayAPI
 * @package Drupal\payment\API
 */
class EcommpayAPI extends PaymentAPIBase {

  use CreditCardAPITrait;

  /**
   * API endpoints.
   */
  const API_GATE_CARD_SANDBOX_URL = 'https://gate-sandbox.ecommpay.com/card/json/',
    API_GATE_CARD_LIVE_URL = 'https://gate.ecommpay.com/card/json/',
    API_GATE_OP_SANDBOX_URL = 'https://gate-sandbox.ecommpay.com/op/json/',
    API_GATE_OP_LIVE_URL = 'https://gate.ecommpay.com/op/json/';

  /**
   * Most important response codes. For full list of codes see
   * http://docs.ecommpay.com/ru/knowledge_base.html#Коды-ответов
   */
  const RESPONSE_CODE_SUCCESS = 0,
    RESPONSE_CODE_PENDING = 50;

  /**
   * Transaction statuses.
   */
  const TRANSACTION_STATUS_EXTERNAL_PROCESSING = 2,
    TRANSACTION_STATUS_AWAITING_CONFIRMATION = 3,
    TRANSACTION_STATUS_SUCCESS = 4,
    TRANSACTION_STATUS_VOID = 5,
    TRANSACTION_STATUS_PROCESSOR_DECLINE = 6,
    TRANSACTION_STATUS_FRAUDSTOP_DECLINE = 7,
    TRANSACTION_STATUS_MPI_DECLINE = 8,
    TRANSACTION_STATUS_SYSTEM_FAILURE = 10,
    TRANSACTION_STATUS_EXPIRED = 13,
    TRANSACTION_STATUS_CANCELED = 14,
    TRANSACTION_STATUS_INTERNAL_ERROR = 15;

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
   * Makes purchase request.
   *
   * @return array
   * @throws \Drupal\payment\API\EcommpayAPIException
   */
  public function doPurchase() {
    if (!isset($this->creditCardData)) {
      throw new EcommpayAPIException('Credit card data is not provided for purchase request.');
    }
    if (!isset($this->transaction)) {
      throw new EcommpayAPIException('Transaction object is not provided for purchase request.');
    }

    if ($this->transaction->isNew()) {
      $this->transaction->save();
    }

    $params = [
      'action' => 'purchase',
      'amount' => (string) ($this->transaction->getAmount()->getNumber() * 100),
      'currency' => $this->transaction->getAmount()->getCurrencyCode(),
      'external_id' => $this->getExternalId(),
      'card' => $this->creditCardData['card_number'],
      'exp_month' => $this->creditCardData['card_expiration_date']['dates']['month'],
      'exp_year' => $this->creditCardData['card_expiration_date']['dates']['year'],
      'cvv' => $this->creditCardData['card_code'],
      'holder' => $this->creditCardData['card_owner'],
      'customer_ip' => $this->request->getClientIp(),
      'description' => substr($this->transaction->getInvoice()->getDescription(), 0, 512),
    ];

    $log_params = $params;
    $log_params['card'] = substr($log_params['card'], strlen($log_params['card']) - 4);
    $log_params['cvv'] = '***';
    $this->transaction->appendLog($log_params);
    $response = $this->apiGateCardRequest($params);
    $this->transaction->appendLog($response);

    return $response;
  }

  /**
   * Makes complete3ds request
   *
   * @return array
   * @throws \Drupal\payment\API\EcommpayAPIException
   */
  public function doComplete3ds() {
    if (!isset($this->transaction)) {
      throw new EcommpayAPIException('Transaction object is not provided for purchase request.');
    }

    $params = [
      'action' => 'complete3ds',
      'transaction_id' => $this->transaction->getRemoteId(),
      'customer_ip' => $this->request->getClientIp(),
      'pa_res' => $this->request->get('PaRes'),
    ];

    $this->transaction->appendLog($params);
    $response = $this->apiGateCardRequest($params);
    $this->transaction->appendLog($response);

    return $response;
  }

  /**
   * Gets order info from Ecommpay.
   *
   * @return array
   * @throws \Drupal\payment\API\EcommpayAPIException
   */
  public function getOrderInfo() {
    if (!isset($this->transaction)) {
      throw new EcommpayAPIException('Transaction object is not provided for purchase request.');
    }

    $id = $this->transaction->getType() == Transaction::TYPE_PAYMENT ? $this->getExternalId() : $this->getExternalId($this->transaction->getParentTransaction());
    $params = [
      'action' => 'order_info',
      'external_id' => $id,
      'type_id' => 1, // TODO Find a way to get correct type_id.
    ];

    $this->transaction->appendLog($params);
    $response = $this->apiGateOpRequest($params);
    $this->transaction->appendLog($response);

    return $response;
  }

  /**
   * Generates signature for the passed array of params.
   *
   * @param array $params
   * @return string
   */
  public function getSignature(array $params) {
    $str = $this->prepareArrayForSignature($params) . ';' . $this->config['salt'];
    return sha1($str);
  }

  /**
   * Makes refund request.
   *
   * @return array
   * @throws \Drupal\payment\API\EcommpayAPIException
   */
  public function doRefund() {
    if (!isset($this->transaction)) {
      throw new EcommpayAPIException('Refund transaction object is not provided for refund request.');
    }
    if (!isset($this->originalTransaction)) {
      throw new EcommpayAPIException('Original transaction object is not provided for refund request.');
    }

    $refundAmount = $this->transaction->getAmount()->multiply(-1);

    $params = [
      'action' => 'refund',
      'transaction_id' => $this->originalTransaction->getRemoteId(),
      'amount' => (string) ($refundAmount->getNumber() * 100),
    ];

    $this->transaction->appendLog($params);
    $response = $this->apiGateCardRequest($params);
    $this->transaction->appendLog($response);

    return $response;
  }

  /**
   * Makes request to card operations endpoint.
   *
   * @param array $params
   * @return array
   */
  protected function apiGateCardRequest(array $params) {
    return $this->apiRequest($this->getUrl('gate_card'), $params);
  }

  /**
   * Makes request to general operations endpoint.
   *
   * @param array $params
   * @return array
   */
  protected function apiGateOpRequest(array $params) {
    return $this->apiRequest($this->getUrl('gate_op'), $params);
  }

  /**
   * Makes a request to API endpoint.
   *
   * @param string $url
   * @param array $params
   * @return array
   * @throws \Drupal\payment\API\EcommpayAPIException
   */
  protected function apiRequest($url, array $params) {
    $params = $this->encodeParams($params);
    $params = array_merge($params, $this->addDefaultParams($params));
    $options = [
      'form_params' => $params,
    ];

    if ($this->config['sandbox_mode'] && !empty($this->config['proxy'])) {
      $options['proxy'] = $this->config['proxy'];
    }
    try {
      $response = $this->httpClient->post($url, $options);
    }
    catch (\Exception $e) {
      throw new EcommpayAPIException('Error occurred during the HTTP request to API endpoint: ' . $e->getMessage());
    }

    return json_decode($response->getBody(), true);
  }

  /**
   * Gets endpoint url according to current configuration and passed $type.
   *
   * @param string $type
   * @return null|string
   * @throws \Drupal\payment\API\EcommpayAPIException
   */
  protected function getUrl($type) {
    if (!isset($this->config['sandbox_mode'])) {
      throw new EcommpayAPIException('Invalid configurations provided.');
    }

    switch ($type) {
      case 'gate_card':
        return $this->config['sandbox_mode'] ? static::API_GATE_CARD_SANDBOX_URL : static::API_GATE_CARD_LIVE_URL;
      case 'gate_op':
        return $this->config['sandbox_mode'] ? static::API_GATE_OP_SANDBOX_URL : static::API_GATE_OP_LIVE_URL;
    }

    return null;
  }

  /**
   * Adds default params to any request.
   *
   * @param array $params
   * @return array
   * @throws \Drupal\payment\API\EcommpayAPIException
   */
  protected function addDefaultParams(array $params) {
    if (!isset($this->config['site_id'], $this->config['salt'])) {
      throw new EcommpayAPIException('Invalid configurations provided.');
    }

    $params['site_id'] = $this->config['site_id'];
    $params['signature'] = $this->getSignature($params);

    return $params;
  }

  /**
   * Creates a string that used for generating of signature.
   *
   * @param array $params
   * @param int $level
   * @param int $max_level_depth
   * @return string
   * @throws \Drupal\payment\API\EcommpayAPIException
   */
  protected function prepareArrayForSignature(array $params, $level = 1, $max_level_depth = 2) {
    if ($level > $max_level_depth) {
      return '';
    }

    $paramsToSign = array();
    ksort($params);
    foreach ($params as $key => $value) {
      $valueToAdd = '';

      switch (true) {
        case is_bool($value):
          $valueToAdd = $value ? '1' : '0';
          break;

        case is_scalar($value) && !is_resource($value):
          $valueToAdd = (string) $value;
          break;

        case is_array($value):
          $valueToAdd = $this->prepareArrayForSignature($value, $level + 1, $max_level_depth);
          break;

        case is_null($value):
          break;

        default:
          throw new EcommpayAPIException('Type of value for key: \"{$key}\" is not supported. Supported types are: boolean, string, array and null.');
          continue 2;
      }

      if ($valueToAdd === '') {
        continue;
      }

      $paramsToSign[$key] = $key . ':' . $valueToAdd;
    }

    return implode(";", $paramsToSign);
  }

  /**
   * Encodes all params as UTF-8 string
   *
   * @param array $params
   * @return array
   */
  protected function encodeParams(array $params) {
    foreach ($params as &$value) {
      if (is_array($value)) {
        $value = $this->encodeParams($value);
      }
      elseif (is_string($value)) {
        $value = Encoding::toUTF8($value);
      }
    }

    return $params;
  }

}
