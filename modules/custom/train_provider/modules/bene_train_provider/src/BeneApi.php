<?php

namespace Drupal\bene_train_provider;

use Drupal\Core\Link;
use Drupal\Core\Url;

class BeneApi {

  protected $sandboxWsdl;

  protected $liveWsdl = 'https://services.bene-system.com/bene-agent/legacy/dsws_2007-06-13/Rail1PortType?wsdl';

  public static $typeNamespace = 'http://bene-ws.b-rail.be/rail1/2007-06-13/types';

  /**
   * @var bool
   */
  protected $live;

  /**
   * @var bool
   */
  protected $log;

  /**
   * @var \SoapClient
   */
  protected $client;

  /**
   * BeneApi constructor.
   *
   * @param array $config
   */
  public function __construct(array $config) {
    $this->live = $config['live'];
    $this->log = $config['log'];
    $this->sandboxWsdl = drupal_get_path('module', 'bene_train_provider') . '/wsdl/sandbox-2007-06-13.wsdl';
  }

  /**
   * Gets trains and prices from BeNe.
   *
   * @param array $params
   * @return mixed|null
   */
  public function trainsAndProductsRequest(array $params) {
    $response = null;
    $function = 'trains-and-products-request';
    try {
      $response = $this->getClient()->__soapCall($function, $params);
    }
    catch (\Exception $exception) {
      watchdog_exception('bene_train_provider', $exception);
    }

    $this->logData($this->getClient()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getClient()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');

    return $response;
  }

  /**
   * Gets trains and prices from BeNe.
   *
   * @param array $params
   * @return mixed|null
   */
  public function bookingRequest(array $params) {
    $response = null;
    $function = 'booking-request';
    try {
      $response = $this->getClient()->__soapCall($function, $params);
      if (!$response) {
        throw new \Exception(date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml', 1);
      }
    }
    catch (\Exception $exception) {
      $this->logEmptyResponse($this->getClient()->__getLastResponse(), $exception->getMessage());
      if ($exception->getCode() == 1) {
        $variables['link'] = Link::fromTextAndUrl($exception->getMessage(), Url::fromUri(file_create_url('public://bene_train_provider/' . $exception->getMessage())))->toString();
        watchdog_exception('bene_train_provider', $exception, 'Request returns null. See attached link to the log file', $variables);
      }
      else {
        watchdog_exception('bene_train_provider', $exception);
      }
    }

    $this->logData($this->getClient()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getClient()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');

    return $response;
  }

  /**
   * Retrieves dossier from BeNe.
   *
   * @param array $params
   * @return mixed|null
   */
  public function retrieveDossierRequest(array $params) {
    $response = null;
    $function = 'retrieve-dossier-request';
    try {
      $response = $this->getClient()->__soapCall($function, $params);
    }
    catch (\Exception $exception) {
      watchdog_exception('bene_train_provider', $exception);
    }

    $this->logData($this->getClient()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getClient()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');

    return $response;
  }

  /**
   * Get cancellation offer request from BeNe.
   *
   * @param array $params
   * @return mixed|null
   */
  public function getCancellationOfferRequest(array $params) {
    $response = null;
    $function = 'get-cancellation-offer-request';
    try {
      $response = $this->getClient()->__soapCall($function, $params);
    }
    catch (\Exception $exception) {
      watchdog_exception('bene_train_provider', $exception);
    }

    $this->logData($this->getClient()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getClient()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');

    return $response;
  }

  /**
   * Execute cancellation request from BeNe.
   *
   * @param array $params
   * @return mixed|null
   */
  public function executeCancellationRequest(array $params) {
    $response = null;
    $function = 'execute-cancellation-request';
    try {
      $response = $this->getClient()->__soapCall($function, $params);
    }
    catch (\Exception $exception) {
      watchdog_exception('bene_train_provider', $exception);
    }

    $this->logData($this->getClient()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getClient()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');

    return $response;
  }

  /**
   * Execute manage fulfillment request from BeNe.
   *
   * @param array $params
   * @return mixed|null
   */
  public function manageFulfillmentRequest(array $params) {
    $response = null;
    $function = 'manage-fulfillment-request';
    try {
      $response = $this->getClient()->__soapCall($function, $params);
      if (!$response) {
        throw new \Exception(date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml', 1);
      }
    }
    catch (\Exception $exception) {
      $this->logEmptyResponse($this->getClient()->__getLastResponse(), $exception->getMessage());
      if ($exception->getCode() == 1) {
        $variables['link'] = Link::fromTextAndUrl($exception->getMessage(), Url::fromUri(file_create_url('public://bene_train_provider/' . $exception->getMessage())))->toString();
        watchdog_exception('bene_train_provider', $exception, 'Request returns null. See attached link to the log file', $variables);
      }
      else {
        watchdog_exception('bene_train_provider', $exception);
      }
    }

    $this->logData($this->getClient()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getClient()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');

    return $response;
  }

  /**
   * Gets trains and prices from BeNe.
   *
   * @param array $params
   * @return mixed|null
   */
  public function cancelBookingRequest(array $params) {
    $response = null;
    $function = 'cancel-booking-request';
    try {
      $response = $this->getClient()->__soapCall($function, $params);
    }
    catch (\Exception $exception) {
      watchdog_exception('bene_train_provider', $exception);
    }

    $this->logData($this->getClient()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getClient()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');

    return $response;
  }

  /**
   * Gets SOAP client.
   *
   * @return \SoapClient
   */
  protected function getClient() {
    if (!$this->client) {
      if ($this->live) {
        $this->client = new \SoapClient($this->liveWsdl, ['version' => SOAP_1_2]);
      }
      else {
        $this->client = new \SoapClient($this->sandboxWsdl, [
          'trace' => true,
          'version' => SOAP_1_2,
          'proxy_host' => '185.38.166.39',
          'proxy_port' => '56789',
        ]);
      }
    }

    return $this->client;
  }

  /**
   * Logs data into the file.
   *
   * @param mixed $data
   * @param string $file_name
   */
  protected function logData($data, $file_name) {
    if ($this->log) {
      $path = 'public://bene_train_provider';
      file_prepare_directory($path, FILE_CREATE_DIRECTORY);
      file_unmanaged_save_data($data, $path . '/' . $file_name);
    }
  }

  /**
   * Logs data into the file.
   *
   * @param mixed $data
   * @param string $file_name
   */
  protected function logEmptyResponse($data, $file_name) {
    $path = 'public://bene_train_provider';
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    file_unmanaged_save_data($data, $path . '/' . $file_name);
  }

}
