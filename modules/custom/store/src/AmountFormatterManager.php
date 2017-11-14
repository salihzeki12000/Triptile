<?php

namespace Drupal\store;

use Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterManager as CurrencyAmountFormatterManager;

class AmountFormatterManager extends CurrencyAmountFormatterManager {

  /**
   * {@inheritdoc}
   */
  public function getDefaultPluginId() {
    return $this->configFactory->get('currency.amount_formatting')
      ->get('plugin_id');
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultPluginId($plugin_id) {
    $this->configFactory->get('currency.amount_formatting')
      ->set('plugin_id', $plugin_id)
      ->save();

    return $this;
  }

}