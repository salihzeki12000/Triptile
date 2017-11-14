<?php

namespace Drupal\store;

use Drupal\currency\PluginBasedExchangeRateProvider;
use Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterManagerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class PriceFactory.
 *
 * @package Drupal\store
 */
class PriceFactory {

  /**
   * Drupal\currency\PluginBasedExchangeRateProvider definition.
   *
   * @var \Drupal\currency\PluginBasedExchangeRateProvider
   */
  protected $currencyExchangeRateProvider;

  /**
   * The currency amount formatter manager.
   *
   * @var \Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterManagerInterface
   */
  protected $currencyAmountFormatterManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;


  /**
   * Constructor.
   * @param \Drupal\currency\PluginBasedExchangeRateProvider $currency_exchange_rate_provider
   * @param \Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterManagerInterface $currency_amount_formatter_manager
   *   The currency amount formatter manager.
   * @param \Drupal\Core\Entity\EntityTypeManager|\Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(PluginBasedExchangeRateProvider $currency_exchange_rate_provider, AmountFormatterManagerInterface $currency_amount_formatter_manager, EntityTypeManager $entity_type_manager) {
    $this->currencyExchangeRateProvider = $currency_exchange_rate_provider;
    $this->currencyAmountFormatterManager = $currency_amount_formatter_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Generates price object
   *
   * @param string $number
   * @param string $currency_code
   * @return \Drupal\store\Price
   */
  public function get($number, $currency_code) {
    return new Price($this->currencyExchangeRateProvider, $this->currencyAmountFormatterManager, $this->entityTypeManager, $number, $currency_code);
  }

}
