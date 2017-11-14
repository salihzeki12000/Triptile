<?php

namespace Drupal\rn_user;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\CacheableMetadata;

class UserCountryCacheContext implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('User country');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return \Drupal::service('master.maxmind')->getCountry();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }
}