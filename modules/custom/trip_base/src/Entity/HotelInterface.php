<?php

namespace Drupal\trip_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Hotel entities.
 *
 * @ingroup trip_base
 */
interface HotelInterface extends  ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Hotel name.
   *
   * @return string
   *   Name of the Hotel.
   */
  public function getName();

  /**
   * Sets the Hotel name.
   *
   * @param string $name
   *   The Hotel name.
   *
   * @return \Drupal\trip_base\Entity\HotelInterface
   *   The called Hotel entity.
   */
  public function setName($name);

  /**
   * Gets the Hotel creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Hotel.
   */
  public function getCreatedTime();

  /**
   * Sets the Hotel creation timestamp.
   *
   * @param int $timestamp
   *   The Hotel creation timestamp.
   *
   * @return \Drupal\trip_base\Entity\HotelInterface
   *   The called Hotel entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Hotel published status indicator.
   *
   * Unpublished Hotel are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Hotel is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Hotel.
   *
   * @param bool $published
   *   TRUE to set this Hotel to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\trip_base\Entity\HotelInterface
   *   The called Hotel entity.
   */
  public function setPublished($published);

  /**
   * Gets the Hotel rating.
   *
   * @return string
   */
  public function getStar();

  /**
   * Sets the Hotel rating.
   *
   * @param string $rating
   * @return string
   */
  public function setStar($star);

  /**
   * Gets the Hotel Hub.
   *
   * @return string
   */
  public function getHub();

  /**
   * Sets the Hotel Hub.
   *
   * @param string $rating
   * @return string
   */
  public function setHub($hub);

  /**
   * Gets the Hotel Address.
   *
   * @return string
   */
  public function getAddress();

  /**
   * Sets the Hotel Address.
   *
   * @param string $country
   * @param string $city
   * @param string $street
   * @return string
   */
  public function setAddress($country, $city, $street);

  /**
   * Gets the Hotel preferred.
   *
   * @return boolean
   */
  public function getPreferred();

  /**
   * Sets the Hotel preferred.
   *
   * @return string
   */
  public function setPreferred($preferred);
}

