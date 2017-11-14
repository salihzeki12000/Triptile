<?php

namespace Drupal\trip_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Hub entities.
 *
 * @ingroup trip_base
 */
interface HubInterface extends  ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Hub name.
   *
   * @return string
   *   Name of the Hub.
   */
  public function getName();

  /**
   * Sets the Hub name.
   *
   * @param string $name
   *   The Hub name.
   *
   * @return \Drupal\trip_base\Entity\HubInterface
   *   The called Hub entity.
   */
  public function setName($name);

  /**
   * Gets the Hub creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Hub.
   */
  public function getCreatedTime();

  /**
   * Sets the Hub creation timestamp.
   *
   * @param int $timestamp
   *   The Hub creation timestamp.
   *
   * @return \Drupal\trip_base\Entity\HubInterface
   *   The called Hub entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Hub published status indicator.
   *
   * Unpublished Hub are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Hub is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Hub.
   *
   * @param bool $published
   *   TRUE to set this Hub to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\trip_base\Entity\HubInterface
   *   The called Hub entity.
   */
  public function setPublished($published);

  /**
   * Gets the country where the hub is located.
   *
   * @return string
   */
  public function getCountry();

  /**
   * Sets the country where the hub is located.
   *
   * @param string $country
   * @return static
   */
  public function setCountry($country);

  /**
   * Gets the region where the hub is located.
   *
   * @return string
   */
  public function getRegion();

  /**
   * Sets the region where the hub is located.
   *
   * @param string $region
   * @return static
   */
  public function setRegion($region);

  /**
   * Gets the hub description.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Gets the hub latitude.
   *
   * @return float
   */
  public function getLatitude();

  /**
   * Gets the hub longitude.
   *
   * @return float
   */
  public function getLongitude();

  /**
   * Gets the hub rating.
   *
   * @return float
   */
  public function getRating();

  /**
   * Gets recommended number of days for the hub.
   *
   * @return int
   */
  public function getRecommendedNumberOfDays();

  /**
   * Checks if the hub can be start point.
   *
   * @return bool
   */
  public function isStartPoint();

  /**
   * Sets the hub rating.
   *
   * @param float $rating
   * @return static
   */
  public function setRating($rating);

  /**
   * Sets recommended number of days for the hub.
   *
   * @param int $days
   * @return static
   */
  public function setRecommendedNumberOfDays($days);

}
