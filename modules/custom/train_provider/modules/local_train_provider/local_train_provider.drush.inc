<?php
/**
 * @file Contains the code to generate the custom drush commands.
 */
/**
 * Implements hook_drush_command().
 */
function local_train_provider_drush_command() {
  $items = array();
  $items['update_timetable_entry_price'] = [
    'description' => 'Update timetable entries prices',
    'drupal dependencies' => ['local_train_provider'],
    'aliases' => ['update-timetable-entry-prices', 'utep'],
    'options' => [
      'train-provider' => 'The provider you would like to use for price updating.'
    ],
  ];
  return $items;
}
/**
 * Call back function drush_local_train_provider_update_timetable_entry_price()
 */
function drush_local_train_provider_update_timetable_entry_price() {
  $trainProvider = drush_get_option('train-provider', '');
  \Drupal::service('local_train_provider.timetable_entry_price_updater')->update($trainProvider);
  drush_print('Timetable entries prices have been updated successfully');
}