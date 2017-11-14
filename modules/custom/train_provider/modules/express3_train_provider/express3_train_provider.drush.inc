<?php
/**
 * @file Contains the code to generate the custom drush commands.
 */
/**
 * Implements hook_drush_command().
 */
function express3_train_provider_drush_command() {
  $items = array();
  $items['create_long_cache'] = [
    'description' => 'Create long cache',
    'drupal dependencies' => ['express3_train_provider'],
    'aliases' => ['create-long-cache', 'clc'],
  ];
  return $items;
}
/**
 * Call back function drush_express3_train_provider_create_long_cache()
 */
function drush_express3_train_provider_create_long_cache() {
  \Drupal::service('express3_train_provider.long_cache')->create();
  drush_print('Long cache has been created!');
}