<?php

namespace Drupal\store;

use Drupal\currency\Plugin\Currency\ExchangeRateProvider\ExchangeRateProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\currency\FormHelper;


class CurrencyFixedRateUpdater {

  /**
   * @var \Drupal\currency\Plugin\Currency\ExchangeRateProvider\ExchangeRateProviderManager
   */
  protected $exchangeRateProviderManager;

  /**
   * @var \Drupal\currency\FormHelper
   */
  protected $currencyFormHelper;

  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.currency.exchange_rate_provider'), $container->get('currency.form_helper'));
  }

  /**
   * Constructor.
   *
   * @param \Drupal\currency\Plugin\Currency\ExchangeRateProvider\ExchangeRateProviderManager
   * @param \Drupal\currency\FormHelper
   */
  public function __construct(ExchangeRateProviderManager $exchange_rate_provider_manager, FormHelper $currency_form_helper) {
    $this->exchangeRateProviderManager = $exchange_rate_provider_manager;
    $this->currencyFormHelper = $currency_form_helper;
  }

  public function updateRates() {
    $currency_options = $this->currencyFormHelper->getCurrencyOptions();
    $fixed_rates = $this->exchangeRateProviderManager->createInstance('currency_fixed_rates');
    //$rates = $fixed_rates->loadAll();
    foreach ($currency_options as $currency_code_from => $value) {
      $symbols = array_keys($currency_options);
      if ($currency_code_from != 'XXX') {
        $response = $this->getRate($currency_code_from, $symbols);
      }
      $fixed_rates->save($response->base, $response->base, 1);
      foreach ($response->rates as $currency_code_to => $rate) {
        $fixed_rates->save($response->base, $currency_code_to, $rate);
      }
    }
  }

  protected function getRate($base, $symbols) {
    $url = 'http://api.fixer.io/latest';
    $url .= '?base=' . $base;
    $url .= '&symbols= ' . implode(',', $symbols);
    $response = \Drupal::httpClient()->get($url);

    return json_decode((string) $response->getBody());
  }

}
