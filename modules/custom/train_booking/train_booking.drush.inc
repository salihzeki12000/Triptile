<?php

/**
 * Implements hook_drush_command().
 */
function train_booking_drush_command() {
  $items = [];
  $items['check_pdf'] = [
    'description' => 'Check all orders, where provider is IT and empty PDF.',
    'drupal_dependencies' => ['train_booking'],
    'aliases' => ['check-pdf'],
  ];

  return $items;
}

function drush_train_booking_check_pdf() {
  \Drupal::service('train_booking.train_booking_manager')->checkPdf();
}

/**
 * Implements hook_drush_master_devify().
 */
function train_booking_drush_master_devify() {
  master_clear_entity_table('booking_stat');
  master_clear_entity_table('failed_search');
  master_clear_entity_table('success_search_detailed');
  \Drupal::database()->truncate('train_booking_logger')->execute();
  drush_print('Cleared stats tables');
}
