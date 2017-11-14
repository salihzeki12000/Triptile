<?php

namespace Drupal\trip_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Connection entities.
 *
 * @ingroup trip_base
 */
interface ConnectionInterface extends  ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Connection name.
   *
   * @return string
   *   Name of the Connection.
   */
  public function getName();

  /**
   * Sets the Connection name.
   *
   * @param string $name
   *   The Connection name.
   *
   * @return \Drupal\trip_base\Entity\ConnectionInterface
   *   The called Connection entity.
   */
  public function setName($name);

  /**
   * Gets the Connection creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Connection.
   */
  public function getCreatedTime();

  /**
   * Sets the Connection creation timestamp.
   *
   * @param int $timestamp
   *   The Connection creation timestamp.
   *
   * @return \Drupal\trip_base\Entity\ConnectionInterface
   *   The called Connection entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Connection published status indicator.
   *
   * Unpublished Connection are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Connection is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Connection.
   *
   * @param bool $published
   *   TRUE to set this Connection to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\trip_base\Entity\ConnectionInterface
   *   The called Connection entity.
   */
  public function setPublished($published);

  /**
   * Gets the connection description.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Gets first point of the connection.
   *
   * @param bool $id
   * @return \Drupal\trip_base\Entity\HubInterface|int
   */
  public function getPointA($id = false);

  /**
   * Gets second point of the connection.
   *
   * @param bool $id
   * @return \Drupal\trip_base\Entity\HubInterface|int
   */
  public function getPointB($id = false);

  /**
   * Gets the connection type.
   *
   * @return string
   */
  public function getType();

  /**
   * Gets trip duration of the connection.
   *
   * @return int
   */
  public function getDuration();

  /**
   * Gets the connection rating.
   *
   * @return float
   */
  public function getRating();

  /**
   * Gets the connection overall rating.
   *
   * @return float
   */
  public function getOverallRating();

  /**
   * Gets all price options of the connection.
   *
   * @return \Drupal\store\Entity\BaseProduct[]
   */
  public function getPriceOptions();

  /**
   * Sets the point A on the connection.
   *
   * @param \Drupal\trip_base\Entity\Hub $pointA
   * @return static
   */
  public function setPointA(Hub $pointA);

  /**
   * Sets the point B on the connection.
   *
   * @param \Drupal\trip_base\Entity\Hub $pointB
   * @return static
   */
  public function setPointB(Hub $pointB);

  /**
   * Sets the point A id on the connection.
   *
   * @param int $pointAId
   * @return static
   */
  public function setPointAId($pointAId);

  /**
   * Sets the point B id on the connection.
   *
   * @param int $pointBId
   * @return static
   */
  public function setPointBId($pointBId);

  /**
   * Sets the connection type.
   *
   * @param string $type
   * @return static
   */
  public function setType($type);

  /**
   * Sets the connection rating.
   *
   * @param float $rating
   * @return static
   */
  public function setRating($rating);

  /**
   * Sets the connection overall rating.
   *
   * @param float $rating
   * @return static
   */
  public function setOverallRating($rating);

  /**
   * Sets price options for the connection.
   *
   * @param array $ids
   * @return static
   */
  public function setPriceOptionsIds($ids);

}
