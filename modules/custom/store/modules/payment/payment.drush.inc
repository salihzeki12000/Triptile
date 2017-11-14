<?php

use Drupal\payment\Entity\Merchant;
use Drupal\payment\Entity\MerchantRouterRule;

/**
 * Implements hook_drush_master_devify().
 */
function payment_drush_master_devify() {
  // Clear all transactions
  master_clear_entity_table('transaction');
  drush_print('Cleared transaction tables');

  // Delete all merchant router rules
  foreach (\Drupal::entityTypeManager()->getStorage('merchant_router_rule')->loadMultiple() as $merchantRouterRule) {
    $merchantRouterRule->delete();
  }

  // Delete all merchants
  foreach (\Drupal::entityTypeManager()->getStorage('merchant')->loadMultiple() as $merchant) {
    $merchant->delete();
  }

  // Create test merchant using simple payment adapter.
  $testMerchant = Merchant::create([
    'id' => 'test',
    'label' => 'Test',
    'status' => true,
    'merchant_id' => 'Test',
    'company_id' => 'Test',
    'payment_methods' => array_keys(\Drupal::service('plugin.manager.payment.payment_method')->getDefinitions()),
    'payment_adapter' => 'simple',
    'adapter_config' => [
      'sandbox_mode' => true,
      'default_currency' => 'USD',
      'supported_currencies' => [],
      'payment_type' => 'success',
    ],
  ]);
  $testMerchant->save();

  // Create test merchant router rule.
  $testMerchantRouterRule = MerchantRouterRule::create([
    'id' => 'test',
    'label' => 'Test',
    'status' => true,
    'weight' => 0,
    'condition' => 'true',
    'merchants' => ['test'],
  ]);
  $testMerchantRouterRule->save();

  drush_print('Created test merchant');
}
