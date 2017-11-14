<?php
/**
 * @file Contains the code to generate the custom drush commands.
 */
/**
 * Implements hook_drush_command().
 */
function russian_trains_migration_drush_command() {
  $items = array();
  $items['update_node_images'] = [
    'description' => 'Update node images',
    'drupal dependencies' => ['store'],
    'aliases' => ['update-node-images', 'uni'],
  ];
  return $items;
}
/**
 * Call back function drush_russian_trains_migration_update_node_images()
 */
function drush_russian_trains_migration_update_node_images() {
  \Drupal::service('russian_trains_migration.node_updater')->updateImages();
  drush_print('Images have been update successfully');
}