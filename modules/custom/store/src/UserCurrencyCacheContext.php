<?php

namespace Drupal\store;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\CacheableMetadata;

class UserCurrencyCacheContext implements CacheContextInterface {

  /**
   * The Default Currency service.
   *
   * @var \Drupal\store\DefaultCurrency
   */
  protected $defaultCurrency;

  /**
   * UserCurrencyCacheContext constructor.
   *
   * @param \Drupal\store\DefaultCurrency $default_currency
   */
  public function __construct(DefaultCurrency $default_currency) {
    $this->defaultCurrency = $default_currency;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('User currency');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->defaultCurrency->getUserCurrency();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }
}