<?php

namespace Drupal\salesforce;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;
use ForceUTF8\Encoding;

/**
 * Class SalesforceApi.
 *
 * @package Drupal\salesforce
 */
class SalesforceApi {

  const
    QUERY_URL = '/services/data/v38.0/query/',
    DELETED_RECORDS_URL = '/services/data/v38.0/sobjects/{object}/deleted/',
    RECORD_URL = '/services/data/v38.0/sobjects/{object}/{id}',
    OBJECT_META_URL = '/services/data/v38.0/sobjects/{object}/describe';


  /**
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(Client $http_client, ConfigFactoryInterface $config_factory, StateInterface $state) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->state = $state;
  }

  /**
   * Generates authorization URL where user will be redirected to authorize the
   * application.
   *
   * @return \Drupal\Core\Url
   */
  public function getRedirectUrl() {
    $url = $this->getConfig()->get('endpoint') . '/services/oauth2/authorize';
    $query = [
      'response_type' => 'code',
      'client_id' => $this->getConfig()->get('consumer_key'),
      'redirect_uri' => Url::fromRoute('salesforce.authorization_callback', [], ['absolute' => true])->toString(),
    ];
    return Url::fromUri($url, ['query' => $query]);
  }

  /**
   * Requests refresh and access tokens from salesforce instance on authorization.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return $this
   * @throws \Drupal\salesforce\SalesforceException
   */
  public function requestToken(Request $request) {
    $url = $this->getConfig()->get('endpoint') . '/services/oauth2/token';
    $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
    $params = [
      'grant_type' => 'authorization_code',
      'client_id' => $this->getConfig()->get('consumer_key'),
      'client_secret' => $this->getConfig()->get('consumer_secret'),
      'redirect_uri' => Url::fromRoute('salesforce.authorization_callback', [], ['absolute' => true])->toString(),
      'code' => $request->get('code'),
    ];
    // @todo replace with appropriate function.
    $pairs = [];
    foreach ($params as $key => $value) {
      $pairs[] = $key . '=' . urlencode($value);
    }

    try {
      $response = $this->httpRequest($url, implode('&', $pairs), $headers, 'POST');
    }
    catch(ClientException $exception) {
      $response = $exception->getResponse();
    }


    if ($response) {
      $body = json_decode($response->getBody());
      if ($response->getStatusCode() != 200) {
        $message = 'Salesforce server returned HTTP response with error '
          . $response->getStatusCode() . ': ' . $response->getReasonPhrase();
        $message .= isset($body->error) ? '; ' . $body->error : '';
        $message .= isset($body->error_description) ? ': ' . $body->error_description . '.' : '.';
        throw new SalesforceException($message, $response->getStatusCode());
      }

      $this->setAccessToken($body->access_token)
        ->setRefreshToken($body->refresh_token)
        ->setInstanceUrl($body->instance_url);
    }

    return $this;
  }

  /**
   * Gets salesforce credentials configuration object.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   */
  protected function getConfig() {
    return $this->configFactory->get('salesforce.credentials');
  }

  /**
   * Sets the OAuth access token.
   *
   * @param string $access_token
   * @return $this
   */
  protected function setAccessToken($access_token) {
    $this->state->set('access_token', $access_token);
    return $this;
  }

  /**
   * Sets the OAuth refresh token.
   *
   * @param string $refresh_token
   * @return $this
   */
  protected function setRefreshToken($refresh_token) {
    $this->configFactory->getEditable('salesforce.credentials')
      ->set('refresh_token', $refresh_token)
      ->save();
    return $this;
  }

  /**
   * Sets the instance URL.
   *
   * @param string $url
   * @return $this
   */
  protected function setInstanceUrl($url) {
    $this->configFactory->getEditable('salesforce.credentials')
      ->set('instance_url', $url)
      ->save();
    return $this;
  }

  /**
   * Gets the OAuth access token.
   *
   * @return string
   */
  protected function getAccessToken() {
    $access_token = $this->state->get('access_token', false);
    if (!$access_token) {
      $this->refreshToken();
    }
    $access_token = $this->state->get('access_token', false);
    return $access_token;
  }

  /**
   * Gets a new access token from salesforce.
   *
   * @return $this
   * @throws \Drupal\salesforce\SalesforceException
   */
  protected function refreshToken() {
    $refresh_token = $this->getConfig()->get('refresh_token');
    if (!$refresh_token) {
      throw new SalesforceException('There is no refresh token.');
    }

    $url = $this->getConfig()->get('endpoint') . '/services/oauth2/token';
    $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
    $params = [
      'grant_type' => 'refresh_token',
      'refresh_token' => $refresh_token,
      'client_id' => $this->getConfig()->get('consumer_key'),
      'client_secret' => $this->getConfig()->get('consumer_secret'),
    ];
    // @todo replace with appropriate function.
    $pairs = [];
    foreach ($params as $key => $value) {
      $pairs[] = $key . '=' . urlencode($value);
    }

    try {
      $response = $this->httpRequest($url, implode('&', $pairs), $headers, 'POST');
    }
    catch(ClientException $exception) {
      $response = $exception->getResponse();
    }

    if ($response) {
      $body = json_decode($response->getBody());
      if ($response->getStatusCode() != 200) {
        $message = 'Salesforce server returned HTTP response with error '
          . $response->getStatusCode() . ': ' . $response->getReasonPhrase();
        $message .= isset($body->error) ? '; ' . $body->error : '';
        $message .= isset($body->error_description) ? ': ' . $body->error_description . '.' : '.';
        throw new SalesforceException($message, $response->getStatusCode());
      }

      $this->setAccessToken($body->access_token)
        ->setInstanceUrl($body->instance_url);
    }

    return $this;
  }

  /**
   * Converts a 15-character case-sensitive Salesforce ID to 18-character
   * case-insensitive ID. If input is not 15-characters, return input unaltered.
   *
   * @param $id
   * @return string
   */
  public function convertId($id) {
    if (strlen($id) != 15) {
      return $id;
    }
    $chunks = str_split($id, 5);
    $extra = '';
    foreach ($chunks as $chunk) {
      $chars = str_split($chunk, 1);
      $bits = '';
      foreach ($chars as $char) {
        $bits .= (!is_numeric($char) && $char == strtoupper($char)) ? '1' : '0';
      }
      $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ012345';
      $extra .= substr($map, base_convert(strrev($bits), 2, 10), 1);
    }
    return $id . $extra;
  }

  /**
   * Makes an API request to the provided path.
   *
   * @param string $path
   * @param array|string|null $params
   * @param string $method
   * @return \stdClass
   * @throws \Drupal\salesforce\SalesforceException
   */
  protected function apiRequest($path, $params = null, $method = 'GET') {
    $headers = array(
      'Authorization' => 'OAuth ' . $this->getAccessToken(),
      'Content-type' => 'application/json',
    );
    $url = $this->getConfig()->get('instance_url') . $path;
    try {
      $response = $this->httpRequest($url, $params, $headers, $method);
    }
    catch(ClientException $exception) {
      $response = $exception->getResponse();
    }

    switch ($response->getStatusCode()) {
      // The session ID or OAuth token used has expired or is invalid.
      case 401:
        $this->refreshToken();
        $headers['Authorization'] = 'OAuth ' . $this->getAccessToken();
        $response = $this->httpRequest($url, $params, $headers, $method);
        // Throw an error if we still have bad response.
        if (!in_array($response->getStatusCode(), array(200, 201, 204))) {
          $body = json_decode($response->getBody());
          $message = 'Salesforce server returned HTTP response with error '
            . $response->getStatusCode() . ': ' . $response->getReasonPhrase() . '.';
          $message .= isset($body->error_description) ? ' ' . $body->error_description . '.' : '';
          throw new SalesforceException($message, $response->getStatusCode());
        }
        break;

      case 200:
      case 201:
      case 204:
        // All good.
        break;
      default:
        if (empty($response)) {
          throw new SalesforceException($response->getReasonPhrase(), $response->getStatusCode());
        }
    }

    $data = json_decode($response->getBody());
    if (is_array($data) && !empty($data[0]) && count($data) == 1) {
      $data = $data[0];
    }

    if (isset($data->error)) {
      throw new SalesforceException($data->error_description, $data->error);
    }

    if (!empty($data->errorCode)) {
      throw new SalesforceException($data->message, $response->getStatusCode());
    }

    return $data;
  }

  /**
   * Makes and HTTP request.
   *
   * @param string $url
   * @param array|string|null $params
   * @param array $headers
   * @param string $method
   * @return mixed|\Psr\Http\Message\ResponseInterface
   */
  protected function httpRequest($url, $params = null, $headers = [], $method = 'GET') {
    $options['headers'] = $headers;
    if ($method == 'GET' && $params) {
      $options['query'] = $params;
    }
    elseif (in_array($method, ['POST', 'PATCH']) && $params) {
      $options['body'] = $params;
    }
    return $this->httpClient->request($method, $url, $options);
  }

  /**
   * Performs a SOQL request.
   *
   * @param \Drupal\salesforce\SelectQuery $query
   * @return array
   */
  public function query(SelectQuery $query) {
    // Do not pass this as an array since http client passes it through urlencode which breaks the query.
    $params = 'q=' . (string) $query;
    $response = $this->apiRequest(static::QUERY_URL, $params);
    $records = $response->records;
    while (isset($response->nextRecordsUrl)) {
      $response = $this->apiRequest($response->nextRecordsUrl);
      $records = array_merge($records, $response->records);
    }

    return $records;
  }

  /**
   * Retrieves the list of individual objects that have been deleted within the
   * given timespan for a specified object type.
   *
   * @param string $salesforce_object
   *   Object type name, E.g., Contact, Account.
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   Start date to check for deleted objects.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_date
   *   End date to check for deleted objects.
   * @return array
   */
  public function getDeleted($salesforce_object, DrupalDateTime $start_date, DrupalDateTime $end_date) {
    $path = str_replace('{object}', $salesforce_object, static::DELETED_RECORDS_URL);
    $timezone = new \DateTimeZone('UTC');
    $params = [
      'start' => $start_date->setTimezone($timezone)->format('c'),
      'end' => $end_date->setTimezone($timezone)->format('c'),
    ];
    $response = $this->apiRequest($path, $params);
    return $response->deletedRecords;
  }

  /**
   * Gets a record from Salesforce.
   *
   * @param string $id
   * @param string $salesforce_object
   * @return \stdClass
   */
  public function getRecord($id, $salesforce_object) {
    $path = str_replace('{object}', $salesforce_object, static::RECORD_URL);
    $path = str_replace('{id}', $id, $path);
    return $this->apiRequest($path);
  }

  /**
   * Updates a record in Salesforce.
   *
   * @param string $id
   * @param string $salesforce_object
   * @param \stdClass $record
   * @return \stdClass
   */
  public function updateRecord($id, $salesforce_object, \stdClass $record) {
    $path = str_replace('{object}', $salesforce_object, static::RECORD_URL);
    $path = str_replace('{id}', $id, $path);
    $params = $this->jsonEncode($record);
    return $this->apiRequest($path, $params, 'PATCH');
  }

  /**
   * Creates a new record in Salesforce.
   *
   * @param string $salesforce_object
   * @param \stdClass $record
   * @return string|null
   */
  public function createRecord($salesforce_object, \stdClass $record) {
    $path = str_replace('{object}', $salesforce_object, static::RECORD_URL);
    $path = str_replace('{id}', '', $path);
    $params = $this->jsonEncode($record);
    $response = $this->apiRequest($path, $params, 'POST');
    return $response->success ? $response->id : null;
  }

  /**
   * Deletes a record in Salesforce.
   *
   * @param string $id
   * @param string $salesforce_object
   * @return \stdClass
   */
  public function deleteRecord($id, $salesforce_object) {
    $path = str_replace('{object}', $salesforce_object, static::RECORD_URL);
    $path = str_replace('{id}', $id, $path);
    return $this->apiRequest($path, null, 'DELETE');
  }

  /**
   * Upsert a record in Salesforce.
   *
   * @param string $field
   * @param string $value
   * @param string $salesforce_object
   * @param \stdClass $record
   * @return string|null
   */
  public function upsertRecord($field, $value, $salesforce_object, \stdClass $record) {
    $path = str_replace('{object}', $salesforce_object, static::RECORD_URL);
    $path = str_replace('{id}', $field . '/' . $value, $path);
    $params = $this->jsonEncode($record);
    $response = $this->apiRequest($path, $params, 'PATCH');
    return $response && $response->success ? $response->id : null;
  }

  /**
   * Gets metadata about an object.
   *
   * @param string $salesforce_object
   * @return \stdClass
   */
  public function describeObject($salesforce_object) {
    $path = str_replace('{object}', $salesforce_object, static::OBJECT_META_URL);
    $response = $this->apiRequest($path);
    return $response;
  }

  /**
   * Encodes an object to JSON format.
   *
   * @param \stdClass $object
   * @return string
   */
  protected function jsonEncode(\stdClass $object) {
    // Encode strings with UTF8 since json_encode fails when encodes non-UTF8 strings.
    foreach ($object as $field => $value) {
      if (is_string($value)) {
        $object->{$field} = Encoding::toUTF8($value);
      }
    }

    return json_encode($object);
  }

}
