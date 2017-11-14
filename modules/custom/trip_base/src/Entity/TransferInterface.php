<?php

namespace Drupal\trip_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Transfer entities.
 *
 * @ingroup trip_base
 */
interface TransferInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Transfer name.
   *
   * @return string
   *   Name of the Transfer.
   */
  public function getName();

  /**
   * Sets the Transfer name.
   *
   * @param string $name
   *   The Transfer name.
   *
   * @return \Drupal\trip_base\Entity\TransferInterface
   *   The called Transfer entity.
   */
  public function setName($name);

  /**
   * Gets the Transfer creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Transfer.
   */
  public function getCreatedTime();

  /**
   * Sets the Transfer creation timestamp.
   *
   * @param int $timestamp
   *   The Transfer creation timestamp.
   *
   * @return \Drupal\trip_base\Entity\TransferInterface
   *   The called Transfer entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Transfer published status indicator.
   *
   * Unpublished Transfer are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Transfer is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Transfer.
   *
   * @param bool $published
   *   TRUE to set this Transfer to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\trip_base\Entity\TransferInterface
   *   The called Transfer entity.
   */
  public function setPublished($published);

  /**
   * Gets the Transfer Hub.
   *
   * @return string
   */
  public function getHub();

  /**
   * Sets the Transfer Hub.
   *
   * @param string $rating
   * @return string
   */
  public function setHub($hub);

}
