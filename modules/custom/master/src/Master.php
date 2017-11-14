<?php

namespace Drupal\master;

class Master {

  /**
   * Company sites.
   */
  const SITE_CODE_RAIL_NINJA = 'RN',
    SITE_CODE_TRIP_TILE = 'TT',
    SITE_CODE_RUSSIAN_TRAINS = 'RT',
    SITE_CODE_FIREBIRD_TOURS = 'FT',
    SITE_CODE_TRAVEL_ALL_RUSSIA = 'TAR',
    SITE_CODE_GOLDEN_RING_CRUISES = 'GRC',
    SITE_CODE_VOLGA_DREAM = 'VD',
    SITE_CODE_RUSSIAN_TRAIN_TICKETS = 'RTT';

  /**
   * Gets the current site code.
   */
  public static function siteCode() {
    return \Drupal::config('master.settings')->get('site_code');
  }

}
