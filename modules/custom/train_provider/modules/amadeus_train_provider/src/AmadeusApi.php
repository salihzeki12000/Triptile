<?php

namespace Drupal\amadeus_train_provider;

use Symfony\Component\DependencyInjection\ContainerInterface;

class AmadeusApi {

  /**
   * List of wsdl urls for sandbox.
   *
   * @var array
   */
  protected static $wsdlSandbox = 'https://demo.contentrail.com/ws/soapApi?wsdl';

  /**
   * List of wsdl urls for live server.
   *
   * @var array
   */
  protected static $wsdlLive = '';

  /**
   * @var bool
   */
  protected $live;

  /**
   * @var \SoapClient
   */
  protected $bookingManager;

  protected $customerManager;

  protected $productManager;

  /**
   * @var \SoapClient
   */
  protected $sessionManager;

  protected $travelManager;

  /**
   * ItApi constructor.
   *
   * @param bool $live
   */
  public function __construct($live = false) {
    $this->live = $live;
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function access(array $params) {
    $response = null;
    try {
      $response = $this->getSessionManager()->__soapCall('ascce', $params);
    }
    catch (\Exception $exception) {
      $op = 1;
      // @todo Log the exception
    }
    return $response;
  }

  /**
   * Gets client connected to booking manager endpoint.
   *
   * @return \SoapClient
   */
  protected function getBookingManager() {
    if (!$this->bookingManager) {
      $wsdl = $this->live ? static::$wsdlLive : static::$wsdlSandbox;
      $this->bookingManager = new ExtendedClient($wsdl, ['trace' => true]);
    }

    return $this->bookingManager;
  }

  /**
   * Gets client connected to product manager endpoint.
   *
   * @return \SoapClient
   */
  protected function getProductManager() {
    if (!$this->productManager) {
      $wsdl = $this->live ? static::$wsdlLive['productManager'] : static::$wsdlSandbox['productManager'];
      $this->productManager = new \SoapClient($wsdl);
    }

    return $this->productManager;
  }


  /**
   * Gets client connected to session manager endpoint.
   *
   * @return \SoapClient
   */
  protected function getSessionManager() {
    if (!$this->sessionManager) {
      $wsdl = $this->live ? static::$wsdlLive['sessionManager'] : static::$wsdlSandbox['sessionManager'];
      $this->sessionManager = new \SoapClient($wsdl);
    }

    return $this->sessionManager;
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function journeySearch(array $params) {
    $response = null;
    try {
      $response = $this->getBookingManager()->__soapCall('acr_JourneySearch', $params);
    }
    catch (\Exception $exception) {
      $op = $this->getBookingManager()->__getLastRequest();
      $op = 1;
      // @todo Log the exception
    }
    return $response;
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function fareOfferSearch(array $params) {
    $response = null;
    try {
      $response = $this->getBookingManager()->__soapCall('acr_FareOfferSearch', $params);
    }
    catch (\Exception $exception) {
      $op = $this->getBookingManager()->__getLastRequest();
      $op = 1;
      // @todo Log the exception
    }
    return $response;
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function carSeatSearch(array $params) {
    $response = null;
    try {
      $response = $this->getBookingManager()->__soapCall('acr_CarSeatSearch', $params);
    }
    catch (\Exception $exception) {
      $op = $this->getBookingManager()->__getLastRequest();
      $op = 1;
      // @todo Log the exception
    }
    return $response;
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function retrieveProductsCatalog(array $params) {
    $response = null;
    try {
      $response = $this->getProductManager()->__soapCall('RetrieveProductsCatalog', $params);
    }
    catch (\Exception $exception) {
      $op = 1;
      // @todo Log the exception
    }
    return $response;
  }

}

class ExtendedClient extends \SoapClient {

  function __construct($wsdl, $options = null) {
    parent::__construct($wsdl, $options);
  }

  function __doRequest($request, $location, $action, $version) {
    $dom = new \DOMDocument('1.0');

    if ($action == 'https://demo.contentrail.com/ws/SoapApi/0.1/acr_JourneySearch') {
      $request = '<?xml version="1.0" encoding="UTF-8"?>
      <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="https://demo.contentrail.com/ws/SoapApi/0.1/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><SOAP-ENV:Body><ns1:acr_JourneySearch><access><resellerCode>FireBird</resellerCode><userName>UserWS</userName><password>sR#eG1zk</password><lang>en</lang></access><originDestinationSearch><originCode>7010720</originCode><originDate>2017-02-24</originDate><originTimeFrom>01:00</originTimeFrom><originTimeTo>22:22</originTimeTo><destinationCode>8799015</destinationCode></originDestinationSearch><roundtripOriginDestinationSearch><originDate xsi:nil="true"/><originTimeFrom xsi:nil="true"/><originTimeTo xsi:nil="true"/></roundtripOriginDestinationSearch><passengers><passenger/><passenger/></passengers></ns1:acr_JourneySearch></SOAP-ENV:Body></SOAP-ENV:Envelope>
      ';
    }

    try {

      //loads the SOAP request to the Document
      $dom->loadXML($request);

    } catch (\DOMException $e) {
      die('Parse error with code ' . $e->code);
    }

    //create a XPath object to query the request
    $path = new \DOMXPath($dom);

    //save the modified SOAP request
    $request = $dom->saveXML();

    //doRequest
    return parent::__doRequest($request, $location, $action, $version);
  }
}