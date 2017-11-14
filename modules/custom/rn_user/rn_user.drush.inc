<?php

/**
 * Implements hook_drush_master_devify().
 */
function rn_user_drush_master_devify() {
  drush_invoke_process('@self', 'sql-sanitize', [], [
    'sanitize-password' => 'password',
    'sanitize-email' => 'test-%uid@test-%uid.com',
  ], ['interactive' => true]);

  \Drupal::database()
    ->update('users_field_data')
    ->expression('name', 'mail')
    ->condition('uid', [0, 1], 'NOT IN')
    ->execute();

  \Drupal::database()
    ->update('users_field_data')
    ->fields(['name' => 'admin'])
    ->condition('uid', 1)
    ->execute();

  drush_print('Sanitized users data');

  // TODO Clear user addresses, phones and names.
}
