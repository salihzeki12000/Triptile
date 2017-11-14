<?php

namespace Drupal\master;

use \Drupal\address\Repository\CountryRepository as CountryRepositoryBase;

class CountryRepository extends CountryRepositoryBase {

  public static function regionsAndCountries() {
    return [
      'scandinavia' => ['NO', 'SE', 'FI', 'DK'],
      'east europe' => ['RU', 'LT'],
      'west europe' => ['ES', 'PT'],
    ];
  }

  /**
   * Gets list of regions available on sites.
   *
   * @return array
   */
  public function getRegions() {
    return [
      'scandinavia' => t('Scandinavia'),
      'east europe' => t('East Europe'),
      'west europe' => t('West Europe'),
    ];
  }

  /**
   * Gets the region the country belongs to.
   *
   * @param string $country
   * @return null|string
   */
  public function getCountryRegion($country) {
    foreach (static::regionsAndCountries() as $region => $countries) {
      if (in_array($country, $countries)) {
        return $region;
      }
    }

    return null;
  }

  /**
   * Gets all countries from the region.
   *
   * @param string $region
   * @return array
   */
  public function getRegionCountries($region) {
    return static::regionsAndCountries()[$region];
  }

}
