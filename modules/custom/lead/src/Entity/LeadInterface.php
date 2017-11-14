<?php

namespace Drupal\lead\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Lead entities.
 *
 * @ingroup lead
 */
interface LeadInterface extends  ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Lead first name.
   *
   * @return string
   *   first name of the Lead.
   */
  public function getFirstName();

  /**
   * Sets the Lead first name.
   *
   * @param string $firstName
   *   The Lead first name.
   *
   * @return static
   */
  public function setFirstName($firstName);

  /**
   * Gets the Lead first name.
   *
   * @return string
   *   last name of the Lead.
   */
  public function getLastName();

  /**
   * Sets the Lead last name.
   *
   * @param string $lastName
   *   The Lead last name.
   *
   * @return static
   */
  public function setLastName($lastName);

  /**
   * Gets the Lead email.
   *
   * @return string
   */
  public function getEmail();

  /**
   * Gets the Lead creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Lead.
   */
  public function getCreatedTime();

  /**
   * Sets the Lead creation timestamp.
   *
   * @param int $timestamp
   *   The Lead creation timestamp.
   *
   * @return \Drupal\lead\Entity\LeadInterface
   *   The called Lead entity.
   */
  public function setCreatedTime($timestamp);

}
