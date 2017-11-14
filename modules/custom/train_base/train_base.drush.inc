<?php

/**
 * Implements hook_drush_master_devify().
 */
function train_base_drush_master_devify() {
  master_clear_entity_table('train_ticket');
  drush_print('Cleared train ticket tables');
  master_clear_entity_table('passenger');
  drush_print('Cleared passenger tables');
}
