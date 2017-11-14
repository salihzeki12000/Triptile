<?php

namespace Drupal\store\Plugin\Currency\AmountFormatter;

use Drupal\currency\Plugin\Currency\AmountFormatter\Basic;
use Commercie\Currency\CurrencyInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Formats amounts using string translation and number_format().
 *
 * @CurrencyAmountFormatter(
 *   description = @Translation("Formats amounts using a translatable string."),
 *   id = "advanced_amount_formatter",
 *   label = @Translation("Advanced Amount Formatter")
 * )
 */
class AdvancedAmountFormatter extends Basic {

  public function formatAmount(CurrencyInterface $currency, $amount, $language_type = LanguageInterface::TYPE_CONTENT) {
    $decimals = 0;
    $currency_locale = $this->localeDelegator->resolveCurrencyLocale();
    $formatted_amount = number_format($amount, $decimals, $currency_locale->getDecimalSeparator(), $currency_locale->getGroupingSeparator());
    if (empty($currency->getSign())) {
      $sign = $currency->getCurrencyCode();
    }
    else {
      $sign = $currency->getSign();
    }
    $arguments = array(
      '@currency_code' => $currency->getCurrencyCode(),
      '@currency_sign' => $sign,
      '@amount' => $formatted_amount,
    );
    switch ($currency->getCurrencyCode()) {
      case 'USD':
        return $this->wrapAmount($arguments, 'left');
      case 'EUR':
        return $this->wrapAmount($arguments, 'right');
      case 'RUB':
        return $this->wrapAmount($arguments, 'right');
      default:
        return $this->wrapAmount($arguments, 'left');
    }
  }

  protected function wrapAmount($arguments, string $currency_position) {
    $wrapper = '<div class="amount-wrapper ' . strtolower($arguments['@currency_code']) . '">';
    $number = '<span class="number">' . $this->t('@amount', $arguments) . '</span>';
    $currency = '<span class="currency">' . $this->t('@currency_sign', $arguments) . '</span>';
    switch ($currency_position) {
      case 'left':
        $wrapper .= $currency . $number;
        break;
      case 'right':
        $wrapper .= $number . $currency;
        break;
    }
    $wrapper .= '</div>';

    return $wrapper;
  }
}