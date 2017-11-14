<?php

namespace Drupal\store;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;


class DefaultCurrency {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('request_stack'));
  }

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
    $this->request = $this->requestStack->getCurrentRequest();
  }

  public function getUserCurrency() {

    // Return current active user's currency.
    $currency_code = $this->request->cookies->get('user_currency');
    if (!empty($currency_code)) {
      return $currency_code;
    }

    // If user currency cookie doesn't set.
    $store_config = $this->configFactory->get('store.settings');
    $user_country_code = \Drupal::service('master.maxmind')->getCountry();
    if (!empty($user_country_code)) {
      $country_currency = $store_config->get('country_currency');

      // Return currency code for user's country.
      foreach ($country_currency as $item) {
        if ($item['country_code'] == $user_country_code) {
          return $item['currency_code'];
        }
      }
    }

    // User's country hasn't found in the config, so return global site currency.
    $currency_code = $store_config->get('global_currency');
    if (!empty($currency_code)) {
      return $currency_code;
    }

    // Global site currency doesn't set, so return 'USD'.
    return 'USD';
  }

}