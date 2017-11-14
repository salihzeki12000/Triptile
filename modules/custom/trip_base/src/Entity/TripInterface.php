<?php

namespace Drupal\trip_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Trip entities.
 *
 * @ingroup trip_base
 */
interface TripInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Trip name.
   *
   * @return string
   *   Name of the Trip.
   */
  public function getName();

  /**
   * Sets the Trip name.
   *
   * @param string $name
   *   The Trip name.
   *
   * @return \Drupal\trip_base\Entity\TripInterface
   *   The called Trip entity.
   */
  public function setName($name);

  /**
   * Gets the Trip creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Trip.
   */
  public function getCreatedTime();

  /**
   * Sets the Trip creation timestamp.
   *
   * @param int $timestamp
   *   The Trip creation timestamp.
   *
   * @return \Drupal\trip_base\Entity\TripInterface
   *   The called Trip entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Trip published status indicator.
   *
   * Unpublished Trip are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Trip is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Trip.
   *
   * @param bool $published
   *   TRUE to set this Trip to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\trip_base\Entity\TripInterface
   *   The called Trip entity.
   */
  public function setPublished($published);

}
