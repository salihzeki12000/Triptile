<?php

namespace Drupal\it_train_provider;

class ItApi {

  /**
   * List of wsdl urls for sandbox.
   *
   * @var array
   */
  protected static $wsdlSandbox = [
    'bookingManager' => 'http://172.26.78.129/BIG/v5/Soap/BookingManager.svc?SingleWsdl',
    'sessionManager' => 'http://172.26.78.129/BIG/v5/Soap/SessionManager.svc?SingleWsdl',
    'customerManager' => 'http://172.26.78.129/BIG/v5/Soap/CustomerManager.svc?SingleWsdl',
    'productManager' => 'http://172.26.78.129/BIG/v5/Soap/ProductManager.svc?SingleWsdl',
    'travelManager' => 'http://172.26.78.129/BIG/v5/Soap/TravelManager.svc?SingleWsdl',

  ];

  /**
   * List of wsdl urls for live server.
   *
   * @var array
   */
  protected static $wsdlLive = [
    /*'bookingManager' => 'https://big.ntvspa.it/BIG/v5/Soap/BookingManager.svc?SingleWsdl',
    'sessionManager' => 'https://big.ntvspa.it/BIG/v5/Soap/SessionManager.svc?SingleWsdl',
    'customerManager' => 'https://big.ntvspa.it/BIG/v5/Soap/CustomerManager.svc?SingleWsdl',
    'productManager' => 'https://big.ntvspa.it/BIG/v5/Soap/ProductManager.svc?SingleWsdl',
    'travelManager' => 'https://big.ntvspa.it/BIG/v5/Soap/TravelManager.svc?SingleWsdl',*/
    'bookingManager' => '/wsdl/BookingManager.svc.xml',
    'sessionManager' => '/wsdl/SessionManager.svc.xml',
    'customerManager' => '/wsdl/CustomerManager.svc.xml',
    'productManager' => '/wsdl/ProductManager.svc.xml',
    'travelManager' => '/wsdl/TravelManager.svc.xml',
  ];

  /**
   * @var bool
   */
  protected $live;

  /**
   * @var bool
   */
  protected $log;

  /**
   * @var bool
   */
  protected $logOnException;

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
   * @param $configuration
   */
  public function __construct($configuration) {
    $this->live = $configuration['live'];
    $this->log = $configuration['log'];
    $this->logOnException = $configuration['log_on_exception'];
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function login(array $params) {
    $response = null;
    $function = 'Login';
    try {
      $response = $this->getSessionManager()->__soapCall($function, $params);
    }
    catch (\Exception $exception) {
      watchdog_exception('it_train_provider', $exception);
    }
    $this->logData($this->getBookingManager()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getBookingManager()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');
    return $response;
  }

  /**
   * Gets client connected to booking manager endpoint.
   *
   * @return \SoapClient
   */
  protected function getBookingManager() {
    if (!$this->bookingManager) {
      $wsdl = $this->live ? drupal_get_path('module', 'it_train_provider') .
        static::$wsdlLive['bookingManager'] : static::$wsdlSandbox['bookingManager'];
      $this->bookingManager = new \SoapClient($wsdl, ['trace' => true, 'version' => SOAP_1_2]);
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
      $wsdl = $this->live ? drupal_get_path('module', 'it_train_provider') .
        static::$wsdlLive['productManager'] : static::$wsdlSandbox['productManager'];
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
      $wsdl = $this->live ? drupal_get_path('module', 'it_train_provider') .
        static::$wsdlLive['sessionManager'] : static::$wsdlSandbox['sessionManager'];
      $this->sessionManager = new \SoapClient($wsdl);
    }

    return $this->sessionManager;
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function getAvailableTrains(array $params) {
    $response = null;
    $function = 'GetAvailableTrains';
    try {
      $response = $this->getBookingManager()->__soapCall($function, $params);
    }
    catch (\Exception $exception) {
      $this->logDataOnException($this->getBookingManager()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-outException.xml');
      $this->logDataOnException($this->getBookingManager()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-inException.xml');
      watchdog_exception('it_train_provider', $exception);
    }
    $this->logData($this->getBookingManager()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getBookingManager()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');

    return $response;
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function holdBooking(array $params) {
    $response = null;
    $function = 'HoldBooking';
    try {
      $response = $this->getBookingManager()->__soapCall($function, $params);
    }
    catch (\Exception $exception) {
      $this->logDataOnException($this->getBookingManager()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-outException.xml');
      $this->logDataOnException($this->getBookingManager()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-inException.xml');
      watchdog_exception('it_train_provider', $exception);
    }
    $this->logData($this->getBookingManager()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getBookingManager()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');

    return $response;
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function managePayment(array $params) {
    $response = null;
    $function = 'ManagePayment';
    try {
      $response = $this->getBookingManager()->__soapCall($function, $params);
    }
    catch (\Exception $exception) {
      $this->logDataOnException($this->getBookingManager()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-outException.xml');
      $this->logDataOnException($this->getBookingManager()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-inException.xml');
      watchdog_exception('it_train_provider', $exception);
    }
    $this->logData($this->getBookingManager()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getBookingManager()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');

    return $response;
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function finalizeBooking(array $params) {
    $response = null;
    $function = 'FinalizeBooking';
    try {
      $response = $this->getBookingManager()->__soapCall($function, $params);
    }
    catch (\Exception $exception) {
      $this->logDataOnException($this->getBookingManager()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-outException.xml');
      $this->logDataOnException($this->getBookingManager()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-inException.xml');
      watchdog_exception('it_train_provider', $exception);
    }
    $this->logData($this->getBookingManager()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getBookingManager()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');

    return $response;
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function getPDFTicket(array $params) {
    $response = null;
    try {
      $response = $this->getBookingManager()->__soapCall('GetPDFTicket', $params);
    }
    catch (\Exception $exception) {
      watchdog_exception('it_train_provider', $exception);
    }
    return $response;
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function getBooking(array $params) {
    $response = null;
    $function = 'GetBooking';
    try {
      $response = $this->getBookingManager()->__soapCall($function, $params);
    }
    catch (\Exception $exception) {
      if ($exception->detail->ServiceFault->Code == 1002) {
        drupal_set_message(t('Booking not found'), 'warning');
      }
      else {
        drupal_set_message(t('Can\'t get a request, provided information is invalid'), 'warning');
      }
      watchdog_exception('it_train_provider', $exception);
    }
    $this->logData($this->getBookingManager()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getBookingManager()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');
    return $response;
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function getBookingFromState(array $params) {
    $response = null;
    $function = 'GetBookingFromState';
    try {
      $response = $this->getBookingManager()->__soapCall($function, $params);
    }
    catch (\Exception $exception) {
      watchdog_exception('it_train_provider', $exception);
    }
    $this->logData($this->getBookingManager()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getBookingManager()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');
    return $response;
  }

  /**
   * @param array $params
   * @return \stdClass|null
   */
  public function deleteJourney(array $params) {
    $response = null;
    $function = 'DeleteJourney';
    try {
      $response = $this->getBookingManager()->__soapCall($function, $params);
    }
    catch (\Exception $exception) {
      if ($exception->detail->ServiceFault->Code == 1023) {
        drupal_set_message(t('The tickets are not cancellable'), 'warning');
      }
      else {
        drupal_set_message(t('Can\'t cancel this route'), 'warning');
      }
      watchdog_exception('it_train_provider', $exception);
    }
    $this->logData($this->getBookingManager()->__getLastRequest(), date('Y-m-d\TH:i:s') . '-' . $function . '-out.xml');
    $this->logData($this->getBookingManager()->__getLastResponse(), date('Y-m-d\TH:i:s') . '-' . $function . '-in.xml');
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
      watchdog_exception('it_train_provider', $exception);
    }
    return $response;
  }

  /**
   * Logs data into the file.
   *
   * @param mixed $data
   * @param string $file_name
   */
  protected function logData($data, $file_name) {
    if ($this->log) {
      $this->doLogging($data, $file_name);
    }
  }

  /**
   * Logs data into the file on exception.
   *
   * @param mixed $data
   * @param string $file_name
   */
  protected function logDataOnException($data, $file_name) {
    if ($this->logOnException) {
      $this->doLogging($data, $file_name);
    }
  }

  /**
   * Logs data into the file.
   *
   * @param mixed $data
   * @param string $file_name
   */
  protected function doLogging($data, $file_name) {
    $path = 'public://it_train_provider';
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    file_unmanaged_save_data($data, $path . '/' . $file_name);
  }
}
