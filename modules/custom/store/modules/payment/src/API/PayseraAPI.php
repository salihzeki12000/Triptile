<?php

namespace Drupal\payment\API;

use Drupal\Core\Url;
use Drupal\payment\API\Paysera\WebToPay;
use Drupal\payment\Plugin\PaymentMethod\PayseraPaymentMethodInterface;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;

class PayseraAPI extends PaymentAPIBase {

  /**
   * Payment statuses.
   */
  const PAYMENT_STATUS_FAILED = 0,
    PAYMENT_STATUS_SUCCESS = 1,
    PAYMENT_STATUS_PENDING = 2,
    PAYMENT_STATUS_ADDITIONAL_INFORMATION = 3;

  const DEFAULT_LANGUAGE = 'ENG';

  /**
   * Maps languages enabled on site to languages supported by Paysera.
   *
   * @var array
   */
  protected static $languageMapping = [
    'ru' => 'RUS',
    'de' => 'GER',
  ];

  /**
   * @var \Drupal\Core\Url
   */
  protected $acceptUrl;

  /**
   * @var \Drupal\Core\Url
   */
  protected $cancelUrl;

  /**
   * @var \Drupal\Core\Url
   */
  protected $callbackUrl;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * PayseraAPI constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  public function __construct(Client $http_client, Request $request) {
    parent::__construct($http_client, $request);
    $this->languageManager = \Drupal::languageManager();
  }

  /**
   * Sets accept url where user will be redirected after a successful payment.
   *
   * @param \Drupal\Core\Url $url
   * @return $this
   */
  public function setAcceptUrl(Url $url) {
    $this->acceptUrl = $url;
    return $this;
  }

  /**
   * Sets cancel url where user will be redirected after a failure or
   * cancellation of a payment.
   *
   * @param \Drupal\Core\Url $url
   * @return $this
   */
  public function setCancelUrl(Url $url) {
    $this->cancelUrl = $url;
    return $this;
  }

  /**
   * Sets callback url where Paysera will send all the details about a payment
   * made.
   *
   * @param \Drupal\Core\Url $url
   * @return $this
   */
  public function setCallbackUrl(Url $url) {
    $this->callbackUrl = $url;
    return $this;
  }

  /**
   * Gets payment url where user should be redirected to complete the payment.
   *
   * @throws \Drupal\payment\API\PayseraAPIException
   */
  public function getPaymentUrl() {
    if (!isset($this->config)) {
      throw new PayseraAPIException('Configuration is not set.');
    }
    if (!isset($this->transaction)) {
      throw new PayseraAPIException('Transaction is not set for generation of payment link.');
    }
    if (!isset($this->acceptUrl)) {
      throw new PayseraAPIException('Accept url is not set for generation of payment link.');
    }
    if (!isset($this->cancelUrl)) {
      throw new PayseraAPIException('Cancel url is not set for generation of payment link.');
    }
    if (!isset($this->callbackUrl)) {
      throw new PayseraAPIException('Callback url is not set for generation of payment link.');
    }

    $langId = $this->languageManager->getCurrentLanguage()->getId();

    $params = [
      'projectid' => $this->config['projectid'],
      'orderid' => $this->getExternalId(),
      'accepturl' => $this->acceptUrl->setAbsolute()->toString(),
      'cancelurl' => $this->cancelUrl->setAbsolute()->toString(),
      'callbackurl' => $this->callbackUrl->setAbsolute()->toString(),
      'sign_password' => $this->config['sign_password'],
      'amount' => $this->transaction->getAmount()->getNumber() * 100,
      'currency' => $this->transaction->getAmount()->getCurrencyCode(),
      'lang' => isset(static::$languageMapping[$langId]) ? static::$languageMapping[$langId] : static::DEFAULT_LANGUAGE,
      //'paytext' => '', // Use from config
    ];

    $paymentMethodId = $this->transaction->getPaymentMethod();
    $configs = \Drupal::configFactory()->get('plugin.plugin_configuration.payment_method.' . $paymentMethodId)->get();
    $paymentMethod = \Drupal::service('plugin.manager.payment.payment_method')->createInstance($paymentMethodId, $configs);

    if ($paymentMethod instanceof PayseraPaymentMethodInterface) {
      $params['payment'] = $paymentMethod->getPayseraPaymentName();
    }

    if (isset($this->billingProfile) && $email = $this->billingProfile->getEmail()) {
      $params['p_email'] = $email;
    }
    else {
      $params['p_email'] = $this->transaction->getInvoice()->getUser()->getEmail();
    }

    if (isset($this->billingProfile)) {
      if ($firstName = $this->billingProfile->getAddress()->getGivenName()) {
        $params['p_firstname'] = $firstName;
      }
      if ($lastName = $this->billingProfile->getAddress()->getFamilyName()) {
        $params['p_lastname'] = $lastName;
      }
      if ($street = $this->billingProfile->getAddress()->getAddressLine1()) {
        $params['p_street'] = $street;
      }
      if ($city = $this->billingProfile->getAddress()->getLocality()) {
        $params['p_city'] = $city;
      }
      if ($state = $this->billingProfile->getAddress()->getAdministrativeArea()) {
        $params['p_state'] = $state;
      }
      if ($postalCode = $this->billingProfile->getAddress()->getPostalCode()) {
        $params['p_zip'] = $postalCode;
      }
      if ($countryCode = $this->billingProfile->getAddress()->getCountryCode()) {
        $params['p_countrycode'] = $countryCode;
      }
    }

    if ($this->config['sandbox_mode']) {
      $params['test'] = 1;
    }

    $query = WebToPay::buildRequest($params);
    $url = Url::fromUri(WebToPay::getPaymentUrl(''), ['query' => $query]);

    // Log request params.
    unset($params['projectid'], $params['sign_password']);
    $this->transaction->appendLog($params);

    return $url;
  }

  /**
   * Validates data from request and returns decoded request parameters.
   *
   * @param array $params
   * @return array
   * @throws \Drupal\payment\API\PayseraAPIException
   */
  public function checkResponse(array $params) {
    if (!isset($this->config)) {
      throw new PayseraAPIException('Configuration is not set.');
    }

    return WebToPay::validateAndParseData($params, $this->config['projectid'], $this->config['sign_password']);
  }

  /**
   * Gets payment methods enabled for the current account.
   *
   * @return \Drupal\payment\API\Paysera\WebToPay_PaymentMethodList
   * @throws \Drupal\payment\API\PayseraAPIException
   */
  public function getPaymentMethodList() {
    if (!isset($this->config)) {
      throw new PayseraAPIException('Configuration is not set.');
    }
    $cid = 'payment.paysera.payment-methods.' . $this->config['projectid'];
    if ($cached = \Drupal::cache()->get($cid)) {
      $paymentMethods = $cached->data;
    }
    else {
      $paymentMethods = WebToPay::getPaymentMethodList($this->config['projectid']);
      \Drupal::cache()->set($cid, $paymentMethods);
    }
    return $paymentMethods;
  }

}
