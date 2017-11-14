<?php

/**
 * Implements hook_drush_command().
 */
function salesforce_drush_command() {
  $items = [];
  $items['salesforce-sync'] = [
    'description' => 'Launch sync on all triggered mapping objects.',
    'drupal_dependencies' => ['salesforce'],
    'aliases' => ['sf-sync'],
  ];

  $items['salesforce-pull'] = [
    'description' => 'Pull records from salesforce',
    'options' => [
      'salesforce-object' => 'A salesforce object name that has to be pulled.'
    ],
    'drupal_dependencies' => ['salesforce'],
    'aliases' => ['sf-pull'],
  ];

  return $items;
}

function drush_salesforce_sync() {
  \Drupal::service('salesforce_sync')->processSync();
}

function drush_salesforce_pull() {
  \Drupal::service('salesforce_sync')->triggerRecordsSync(drush_get_option('salesforce-object', null));
}

/**
 * Implements hook_drush_master_devify().
 */
function salesforce_drush_master_devify() {
  // Delete all mapping objects
  master_clear_entity_table('salesforce_mapping_object');
  drush_print('Cleared salesforce mapping object tables');

  // Connect to SF sandbox
  $config = \Drupal::configFactory()->getEditable('salesforce.credentials');
  $config->set('consumer_key', '3MVG9Nc1qcZ7BbZ0Ep18pfQsltZhHdjiEesAiYSxhk1qidi8EgXvbh4ie4qPtsHBQI7R_tNMUXQ==');
  $config->set('consumer_secret', '2230055970094047528');
  $config->set('endpoint', 'https://test.salesforce.com');
  $config->set('refresh_token', '');
  $config->set('instance_url', '');
  $config->save();
  drush_print('Connected to Salesforce sandbox');
}
