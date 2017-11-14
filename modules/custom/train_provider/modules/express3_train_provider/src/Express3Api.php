<?php

namespace Drupal\express3_train_provider;

class Express3Api {

  const
    QUERY_FORMAT_TIMELESS = '%s?query=P62G63C%sC%sD%sZ%%C2',
    QUERY_FORMAT_WITHTIME = '%s?query=P62G63C%sC%sD%sT%s-%sZ%%C2';

  /**
   * Express web service URL
   *
   * @var string
   */
  protected $url = 'http://e-3.tsi.ru/cgi-bin/jatutu/e3';

  /**
   * @var bool
   */
  protected $live;

  /**
   * @var bool
   */
  protected $useLocalFile;

  /**
   * BeneApi constructor.
   *
   * @param array $config
   */
  public function __construct(array $config) {
    $this->live = isset($config['live']) ? $config['live'] : false;
    $this->useLocalFile = isset($config['use_local_file']) ? $config['use_local_file'] : false;
  }

  /**
   * Gets client connected to booking manager endpoint.
   *
   * @param $params
   * @return mixed
   */
  public function getTimetable($params) {
    $response = null;
    try {
      if (isset($params['time_from'], $params['time_to'])) {
        $query = sprintf(self::QUERY_FORMAT_WITHTIME, $this->url, $params['departure_station'], $params['arrival_station'], $params['departure_date'], $params['time_from'], $params['time_to']);
      }
      else {
        $query = sprintf(self::QUERY_FORMAT_TIMELESS, $this->url, $params['departure_station'], $params['arrival_station'], $params['departure_date']);
      }
      if ($this->live) {
        $response = \Drupal::httpClient()->get($query);
        $response = $response->getBody()->__toString();
        if ($response) {
          $response = simplexml_load_string($response);
        }
      }
      else {
        if ($this->useLocalFile) {
          //$xmlPath = drupal_get_path('module', 'express3_train_provider') . '/xml/Omsk-Petropavlovsk.xml';
          $xmlPath = drupal_get_path('module', 'express3_train_provider') . '/xml/Moscow-St.Petersburg.xml';
          if (file_exists($xmlPath)) {
            $response = simplexml_load_file($xmlPath);
          }
        }
        else {
          $response = \Drupal::httpClient()->get($query, ['proxy' => 'socks5://127.0.0.1:1080']);
          $response = $response->getBody()->__toString();
          if ($response) {
            $response = simplexml_load_string($response);
          }
        }
      }
    }
    catch (\Exception $exception) {
      watchdog_exception('express3_train_provider', $exception);
    }
    return $response;
  }

}