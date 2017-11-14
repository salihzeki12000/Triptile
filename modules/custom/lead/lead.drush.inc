<?php

/**
 * Implements hook_drush_master_devify().
 */
function lead_drush_master_devify() {
  master_clear_entity_table('lead');
  drush_print('Cleared lead tables');
}
