<?php

namespace Drupal\trip_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Activity entities.
 *
 * @ingroup trip_base
 */
interface ActivityInterface extends  ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Activity name.
   *
   * @return string
   *   Name of the Activity.
   */
  public function getName();

  /**
   * Sets the Activity name.
   *
   * @param string $name
   *   The Activity name.
   *
   * @return \Drupal\trip_base\Entity\ActivityInterface
   *   The called Activity entity.
   */
  public function setName($name);

  /**
   * Gets the Activity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Activity.
   */
  public function getCreatedTime();

  /**
   * Sets the Activity creation timestamp.
   *
   * @param int $timestamp
   *   The Activity creation timestamp.
   *
   * @return \Drupal\trip_base\Entity\ActivityInterface
   *   The called Activity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Activity published status indicator.
   *
   * Unpublished Activity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Activity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Activity.
   *
   * @param bool $published
   *   TRUE to set this Activity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\trip_base\Entity\ActivityInterface
   *   The called Activity entity.
   */
  public function setPublished($published);

  /**
   * Gets the Activity Hub.
   *
   * @return string
   */
  public function getHub();

  /**
   * Sets the Activity Hub.
   *
   * @param string $rating
   * @return string
   */
  public function setHub($hub);

}
