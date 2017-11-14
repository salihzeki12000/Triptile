<?php

namespace Drupal\store;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\currency\PluginBasedExchangeRateProvider;
use Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterManagerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Price class.
 */
class Price {

  use DependencySerializationTrait;

  /**
   * Default scale used in bcmath functions in the class.
   *
   * @var int
   */
  protected static $scale = 6;

  /**
   * The number.
   *
   * @var string
   */
  protected $number;

  /**
   * The currency code.
   *
   * @var string
   */
  protected $currencyCode;

  /**
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
   * Constructs a new Price object.
   *
   * @param \Drupal\currency\PluginBasedExchangeRateProvider
   * @param \Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterManagerInterface $currency_amount_formatter_manager
   *   The currency amount formatter manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param string $number
   *   The number.
   * @param string $currency_code
   *   The currency code.
   */
  public function __construct(PluginBasedExchangeRateProvider $rate_provider, AmountFormatterManagerInterface $currency_amount_formatter_manager, EntityTypeManager $entity_type_manager, $number, $currency_code) {
    $this->assertNumberFormat($number);
    $this->assertCurrencyCodeFormat($currency_code);

    $this->currencyExchangeRateProvider = $rate_provider;
    $this->currencyAmountFormatterManager = $currency_amount_formatter_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->number = (string) $number;
    $this->currencyCode = strtoupper($currency_code);
  }

  /**
   * Gets the number.
   *
   * @return string
   *   The number.
   */
  public function getNumber() {
    $number = round($this->number);
    return $number;
  }

  /**
   * Gets the currency code.
   *
   * @return string
   *   The currency code.
   */
  public function getCurrencyCode() {
    return $this->currencyCode;
  }

  /**
   * Gets the string representation of the price.
   *
   * @return string
   *   The string representation of the price.
   */
  public function __toString() {
    // @todo is it correct format for outputing price?
    /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
    $currency = $this->entityTypeManager->getStorage('currency')->load($this->currencyCode);
    return (string) $this->currencyAmountFormatterManager->getDefaultPlugin()->formatAmount($currency, $this->getNumber());
  }

  /**
   * Gets the string representation of the price.
   *
   * @param string $formatter
   *  Formatter plugin id
   * @return string
   */
  public function format(string $formatter) {
    $currency = $this->entityTypeManager->getStorage('currency')->load($this->currencyCode);
    return $this->currencyAmountFormatterManager->createInstance($formatter)->formatAmount($currency, $this->getNumber());
  }

  /**
   * Converts the current price to the given currency.
   *
   * @param string $currency_code
   *   The currency code.
   *
   * @return static
   *   The resulting price.
   */
  public function convert($currency_code) {
    $rate = $this->currencyExchangeRateProvider->load($this->currencyCode, $currency_code);
    $new_number = bcmul($this->number, $rate->getRate(), static::$scale);
    return new static($this->currencyExchangeRateProvider, $this->currencyAmountFormatterManager, $this->entityTypeManager, $new_number, $currency_code);
  }

  /**
   * Adds the given price to the current price. The given price will be
   *   converted to the current price currency.
   *
   * @param \Drupal\store\Price $price
   *   The price.
   *
   * @return static
   *   The resulting price.
   */
  public function add(Price $price) {
    $new_price = $price->convert($this->currencyCode);
    $new_number = bcadd($this->number, $new_price->getNumber(), static::$scale);
    return new static($this->currencyExchangeRateProvider, $this->currencyAmountFormatterManager, $this->entityTypeManager, $new_number, $this->currencyCode);
  }

  /**
   * Subtracts the given price from the current price. The given price will be
   *   converted to the current price currency.
   *
   * @param \Drupal\store\Price $price
   *   The price.
   *
   * @return static
   *   The resulting price.
   */
  public function subtract(Price $price) {
    $new_price = $price->convert($this->currencyCode);
    $new_number = bcsub($this->number, $new_price->getNumber(), static::$scale);
    return new static($this->currencyExchangeRateProvider, $this->currencyAmountFormatterManager, $this->entityTypeManager, $new_number, $this->currencyCode);
  }

  /**
   * Multiplies the current price by the given number.
   *
   * @param string $number
   *   The number.
   *
   * @return static
   *   The resulting price.
   */
  public function multiply($number) {
    $new_number = bcmul($this->number, $number, static::$scale);
    return new static($this->currencyExchangeRateProvider, $this->currencyAmountFormatterManager, $this->entityTypeManager, $new_number, $this->currencyCode);
  }

  /**
   * Divides the current price by the given number.
   *
   * @param string $number
   *   The number.
   *
   * @return static
   *   The resulting price.
   */
  public function divide($number) {
    $new_number = bcdiv($this->number, $number, static::$scale);
    return new static($this->currencyExchangeRateProvider, $this->currencyAmountFormatterManager, $this->entityTypeManager, $new_number, $this->currencyCode);
  }

  /**
   * Compares the current price with the given price. The given price will be
   *   converted to the current price currency.
   *
   * @param \Drupal\store\Price $price
   *   The price.
   *
   * @return int
   *   0 if both prices are equal, 1 if the current one is greater, -1 otherwise.
   */
  public function compareTo(Price $price) {
    $new_price = $price->convert($this->currencyCode);
    return bccomp($this->number, $new_price->getNumber(), static::$scale);
  }

  /**
   * Gets whether the current price is zero.
   *
   * @return bool
   *   TRUE if the price is zero, FALSE otherwise.
   */
  public function isZero() {
    return bccomp($this->number, '0', static::$scale) == 0;
  }

  /**
   * Gets whether the current price is equivalent to the given price.
   *
   * @param \Drupal\store\Price $price
   *   The price.
   *
   * @return bool
   *   TRUE if the prices are equal, FALSE otherwise.
   */
  public function equals(Price $price) {
    return $this->compareTo($price) == 0;
  }

  /**
   * Gets whether the current price is greater than the given price.
   *
   * @param \Drupal\store\Price $price
   *   The price.
   *
   * @return bool
   *   TRUE if the current price is greater than the given price,
   *   FALSE otherwise.
   */
  public function greaterThan(Price $price) {
    return $this->compareTo($price) == 1;
  }

  /**
   * Gets whether the current price is greater than or equal to the given price.
   *
   * @param \Drupal\store\Price $price
   *   The price.
   *
   * @return bool
   *   TRUE if the current price is greater than or equal to the given price,
   *   FALSE otherwise.
   */
  public function greaterThanOrEqual(Price $price) {
    return $this->greaterThan($price) || $this->equals($price);
  }

  /**
   * Gets whether the current price is lesser than the given price.
   *
   * @param \Drupal\store\Price $price
   *   The price.
   *
   * @return bool
   *   TRUE if the current price is lesser than the given price,
   *   FALSE otherwise.
   */
  public function lessThan(Price $price) {
    return $this->compareTo($price) == -1;
  }

  /**
   * Gets whether the current price is lesser than or equal to the given price.
   *
   * @param \Drupal\store\Price $price
   *   The price.
   *
   * @return bool
   *   TRUE if the current price is lesser than or equal to the given price,
   *   FALSE otherwise.
   */
  public function lessThanOrEqual(Price $price) {
    return $this->lessThan($price) || $this->equals($price);
  }

  /**
   * Asserts that the currency code is in the right format.
   *
   * Serves only as a basic sanity check.
   *
   * @param string $currency_code
   *   The currency code.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the currency code is not in the right format.
   */
  protected function assertCurrencyCodeFormat($currency_code) {
    if (strlen($currency_code) != '3') {
      throw new \InvalidArgumentException();
    }
  }

  /**
   * Assert that the given number is a numeric string value.
   *
   * @param string $number
   *   The number to check.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the given number is not a numeric string value.
   */
  protected function assertNumberFormat($number) {
    // @todo Do we need it?
    /*if (is_float($number)) {
      throw new \InvalidArgumentException(sprintf('The provided value "%s" must be a string, not a float.', $number));
    }*/
    if (!is_numeric($number)) {
      throw new \InvalidArgumentException(sprintf('The provided value "%s" is not a numeric value.', $number));
    }
  }

}
