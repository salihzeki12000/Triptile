<?php

namespace Drupal\master;

use Drupal\Core\File\FileSystem;
use Drupal\Core\Logger\LoggerChannelFactory;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;

class MasterMaxMind {

  const MAXMIND_GEOLITE2_DOWNLOAD_BASE_URL = 'http://geolite.maxmind.com/download/geoip/database';
  const MAXMIND_GEOLITE2_FILENAME_CITY = 'GeoLite2-City';

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $factory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new Maxmind.
   *
   * @param \Drupal\Core\File\FileSystem
   *   The file system.
   * @param \Drupal\Core\Logger\LoggerChannelFactory
   *   The Logger.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(FileSystem $file_system, LoggerChannelFactory $factory, RequestStack $request_stack) {
    $this->fileSystem = $file_system;
    $this->loggerFactory = $factory;
    $this->requestStack = $request_stack;
  }

  /**
   * Get country by IP.
   *
   * @param string $ip_address
   * @param string $language
   *
   * @return array|bool
   */
  public function getCountry($ip_address = NULL, $language = 'en') {
    $info = $this->getInfoByIp($ip_address, $language);
    return !empty($info['country_code']) ? $info['country_code'] : '';
  }

  /**
   * Get city by IP.
   *
   * @param string $ip_address
   * @param string $language
   *
   * @return array|string
   */
  public function getCity($ip_address = NULL, $language = 'en') {
    $info = $this->getInfoByIp($ip_address, $language);
    return !empty($info['city']) ? $info['city'] : '';
  }

  /**
   * Get city and country by IP.
   *
   * @param string $ip_address
   * @param string $language
   *
   * @return array
   */
  public function getInfoByIp($ip_address = NULL, $language = 'en') {
    if (empty($ip_address)) {
      $ip_address = $this
        ->requestStack
        ->getCurrentRequest()
        ->getClientIp();
    }
    $db_path = $this
      ->fileSystem
      ->realpath(file_stream_wrapper_uri_normalize('public://maxmind'));
    $filename = MasterMaxMind::MAXMIND_GEOLITE2_FILENAME_CITY . '.mmdb';
    $db_path = "$db_path/$filename";
    if (class_exists('\MaxMind\Db\Reader')) {
      if (file_exists($db_path) && filter_var($ip_address, FILTER_VALIDATE_IP)) {
        $reader = new \MaxMind\Db\Reader($db_path);
        $record = $reader->get($ip_address);
        if (!empty($record)) {
          return [
            'continent_code' => !empty($record['continent']['code']) ? $record['continent']['code'] : '',
            'continent_name' => !empty($record['continent']['names'][$language]) ? $record['continent']['names'][$language] : '',
            'country_code' => !empty($record['country']['iso_code']) ? $record['country']['iso_code'] : '',
            'country_name' => !empty($record['country']['names'][$language]) ? $record['country']['names'][$language] : '',
            'region' => !empty($record['subdivisions'][0]['iso_code']) ? $record['subdivisions'][0]['iso_code'] : '',
            'region_name' => !empty($record['subdivisions'][0]['names'][$language]) ? $record['subdivisions'][0]['names'][$language] : '',
            'city' => !empty($record['city']['names'][$language]) ? $record['city']['names'][$language] : '',
            'postal_code' => !empty($record['postal']['code']) ? $record['postal']['code'] : '',
            'latitude' => !empty($record['location']['latitude']) ? $record['location']['latitude'] : '',
            'longitude' => !empty($record['location']['longitude']) ? $record['location']['longitude'] : '',
            'time_zone' => !empty($record['location']['time_zone']) ? $record['location']['time_zone'] : '',
          ];
        }
      }
    }

    if (function_exists('geoip_record_by_name')) {
      return geoip_record_by_name($ip_address);
    }
    return [];
  }

  /**
   * Download a Maxmind database and activate it for use.
   */
  public function dbUpdate() {
    $filename = MasterMaxMind::MAXMIND_GEOLITE2_FILENAME_CITY;
    $path = file_stream_wrapper_uri_normalize('public://maxmind');
    if (!file_prepare_directory($path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      // Private file system path not defined then stop the process
      drupal_set_message(t('Public file system path not defined. Please define the public file system path.'), 'error');
      return FALSE;
    }

    $url = MasterMaxMind::MAXMIND_GEOLITE2_DOWNLOAD_BASE_URL . '/' . $filename . '.tar.gz';
    $gz_name = "$path/$filename.tar.gz";

    if (file_put_contents($gz_name, fopen($url, 'r')) === FALSE) {
      $this
        ->loggerFactory
        ->get('maxmind')
        ->warning('MaxMind database download failed');
      return FALSE;
    }

    if (class_exists('PharData')) {
      $p = new \PharData($this->fileSystem->realpath($gz_name));
      $p->extractTo($this->fileSystem->realpath("$path/db_tmp"));
    }
    else {
      throw new Exception('Server does not have Phar extension installed');
    }

    try {
      $files = file_scan_directory("$path/db_tmp", "/^$filename.mmdb$/");
      $target_filename = "$filename.mmdb";
      if (empty($files) || count($files) !== 1) {
        throw new Exception("Unable to determine the contents of the db_tmp directory ($path/db_tmp)");
      }
      foreach ($files as $file) {
        if (rename($this->fileSystem->realpath($file->uri), $this->fileSystem->realpath("$path/$target_filename")) === FALSE) {
          throw new Exception('Failed to activate the downloaded database');
        }
      }
    } catch (Exception $e) {
      $this
        ->loggerFactory
        ->get('maxmind')
        ->warning('Error during MaxMind database download/extraction: %error', ['%error' => $e->getMessage()]);
      return FALSE;
    }
    $this
      ->loggerFactory
      ->get('maxmind')
      ->info('The MaxMind database has been updated via cron.');
    file_unmanaged_delete($gz_name);
    file_unmanaged_delete_recursive("$path/db_tmp");

    return TRUE;
  }

}
