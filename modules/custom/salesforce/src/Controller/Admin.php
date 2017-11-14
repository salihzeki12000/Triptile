<?php

namespace Drupal\salesforce\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\salesforce\SalesforceApi;
use Drupal\salesforce\SalesforceException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Admin.
 *
 * @package Drupal\salesforce\Controller
 */
class Admin extends ControllerBase {

  /**
   * @var \Drupal\salesforce\SalesforceApi
   */
  protected $salesforceApi;

  /**
   * Admin constructor.
   *
   * @param \Drupal\salesforce\SalesforceApi $salesforce_api
   */
  public function __construct(SalesforceApi $salesforce_api) {
    $this->salesforceApi = $salesforce_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('salesforce_api'));
  }

  /**
   * Allows access to the callback URL only if GET parameter 'code' is set.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Drupal\Core\Access\AccessResult
   */
  public function accessCallback(Request $request) {
    return AccessResult::allowedIf((bool) $request->get('code'));
  }

  /**
   * Authorization callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return string
   */
  public function callback(Request $request) {
    try {
      $this->salesforceApi->requestToken($request);
      drupal_set_message($this->t('Application authorized successfully.'));
    }
    catch (\Exception $exception) {
      watchdog_exception('salesforce', $exception);
      drupal_set_message($this->t('Error occurred during authorization.'), 'error');
    }

    return $this->redirect('salesforce.admin_configuration');
  }

}
