<?php

namespace Drupal\payment\Plugin\PaymentAdapter;

use Drupal\Core\Url;

trait OffSitePaymentTrait {

  /**
   * @var \Drupal\Core\Url
   */
  protected $successUrl;

  /**
   * @var \Drupal\Core\Url
   */
  protected $failUrl;

  /**
   * @var \Drupal\Core\Url
   */
  protected $cancelUrl;

  /**
   * @var \Drupal\Core\Url
   */
  protected $paymentUrl;

  /**
   * {@inheritdoc}
   */
  public function getPaymentUrl() {
    return $this->paymentUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function setSuccessUrl(Url $url) {
    $this->successUrl = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCancelUrl(Url $url) {
    $this->cancelUrl = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFailUrl(Url $url) {
    $this->failUrl = $url;
    return $this;
  }

}
