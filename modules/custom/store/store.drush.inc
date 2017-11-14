<?php

/**
 * Implements hook_drush_command().
 */
function store_drush_command() {
  $items = array();
  $items['update_currency_rates'] = [
    'description' => 'Update currency rates',
    'drupal dependencies' => ['store'],
    'aliases' => ['update-currency-rates', 'ucr'],
  ];
  return $items;
}
/**
 * Call back function drush_store_update_currency_rates()
 */
function drush_store_update_currency_rates() {
  $rates_updater = \Drupal::service('store.currency_fixed_rate_updater');
  $rates_updater->updateRates();
  drush_print('Currency rates have been updated successfully');
}

/**
 * Implements hook_drush_master_devify().
 */
function store_drush_master_devify() {
  master_clear_entity_table('order_item');
  drush_print('Cleared order item tables');
  master_clear_entity_table('store_order');
  drush_print('Cleared order tables');
  master_clear_entity_table('invoice');
  drush_print('Cleared invoice tables');
  master_clear_entity_table('customer_profile');
  drush_print('Cleared customer profile tables');
}
