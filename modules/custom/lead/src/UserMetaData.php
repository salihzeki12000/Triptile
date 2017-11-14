<?php

namespace Drupal\lead;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class UserMetaData
 *
 * @package Drupal\lead
 */
class UserMetaData {

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * UserMetaData constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(RequestStack $request_stack) {
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Gets the user GA client id.
   *
   * @return string
   */
  public function getGaClientId() {
    $gaCookie = $this->request->cookies->get('_ga', '');
    return $gaCookie ? '' : substr($gaCookie, 6);
  }

}
