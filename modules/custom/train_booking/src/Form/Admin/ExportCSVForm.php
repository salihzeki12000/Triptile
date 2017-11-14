<?php

namespace Drupal\train_booking\Form\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\train_booking\TrainBookingLogger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * Class ExportCSVForm.
 *
 * @package Drupal\train_booking\Form
 */
class ExportCSVForm extends FormBase {

  /**
   * The Train Booking Logger service.
   *
   * @var \Drupal\train_booking\TrainBookingLogger
   */
  protected $trainBookingLogger;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * Constructs ExportCSVForm object.
   *
   * @param \Drupal\train_booking\TrainBookingLogger $train_booking_logger
   *   The Train Booking Logger service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   */
  public function __construct(TrainBookingLogger $train_booking_logger, LinkGeneratorInterface $link_generator) {
    $this->trainBookingLogger = $train_booking_logger;
    $this->linkGenerator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('train_booking.logger'),
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'export_csv_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['date_search'] = [
      '#type' => 'date',
      '#title' => t('Start from date'),
      '#description' => $this->t('Leave empty to get all available log entries.'),
    ];

    $form['limit'] = [
      '#type' => 'number',
      '#title' => t('Number of rows'),
      '#default_value' => 0,
      '#description' => $this->t('Set 0 to get all available log entries.')
    ];

    $form['actions']['start_export'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start export'),
      '#weight' => 100,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('train_booking.settings');
    $delimiter = $config->get('delimiter');
    $date_search = $form_state->getValue('date_search');
    $count_record = $form_state->getValue('limit', 0);
    $conditions = [];
    $operations = [];
    if (!empty($date_search)) {
      $conditions[] = [
        'field' => 'search_datetime',
        'value' => $date_search,
        'operator' => '>=',
      ];
    }
    $logs = $this->trainBookingLogger->getData($conditions, $count_record);
    if (!empty($logs)) {
      foreach ($logs as $log) {
        $data = [
          'log' => $log,
          'delimiter' => $delimiter,
        ];
        $operations[] = [[$this, 'processItem'], [$data]];
      }
    }
    $batch = [
      'operations' => $operations,
      'finished' => [$this, 'batchFinished'],
      'title' => $this->t('Exporting CSV ...'),
    ];
    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public function batchFinished($success, $results, $operations) {
    if (!empty($results['file_path'])) {
      $download_url = file_create_url($results['file_path']);
      $link = $this->linkGenerator->generate($this->t('Download csv file!'), Url::fromUri($download_url));
      drupal_set_message($link);
    }
    else {
      drupal_set_message($this->t('Unable to download file!'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data, &$context) {
    $log = (array) $data['log'];
    $delimiter = $data['delimiter'];
    $filename = 'csv_export.csv';
    if (empty($context['results']['file_path'])) {
      $header_csv = array_keys($log);
      $header_csv = implode($delimiter, $header_csv) . PHP_EOL;
      $path = file_stream_wrapper_uri_normalize('public://train_booking');
      file_prepare_directory($path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
      $file_path = $path . '/' . $filename;
      file_put_contents($file_path, $header_csv);
      $context['results']['file_path'] = $file_path;
    }
    $current = implode($delimiter, $log) . PHP_EOL;
    file_put_contents($context['results']['file_path'], $current, FILE_APPEND | LOCK_EX);
  }

}
