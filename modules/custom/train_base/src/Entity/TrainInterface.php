<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Train entities.
 *
 * @ingroup train_base
 */
interface TrainInterface extends ContentEntityInterface, EntityChangedInterface, ReferencingToSupplier {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Train  name.
   *
   * @return string
   */
  public function getName();

  /**
   * Gets the Train number.
   *
   * @return string
   */
  public function getNumber();

  /**
   * Gets the Train creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Train.
   */
  public function getCreatedTime();

  /**
   * Sets the Train creation timestamp.
   *
   * @param int $timestamp
   *   The Train creation timestamp.
   *
   * @return \Drupal\train_base\Entity\TrainInterface
   *   The called Train entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets train brand entity.
   *
   * @return \Drupal\train_base\Entity\TrainBrand
   */
  public function getTrainBrand();

  /**
   * Gets train class entity.
   *
   * @return \Drupal\train_base\Entity\TrainClass
   */
  public function getTrainClass();

  /**
   * Gets the Train TP rating.
   *
   * @return float
   */
  public function getTPRating();

  /**
   * Gets the Train internal rating.
   *
   * @return float
   */
  public function getInternalRating();

  /**
   * Gets the Train count of reviews.
   *
   * @return int
   */
  public function getCountOfReviews();

  /**
   * Gets the train calculated average rating.
   *
   * @return float
   */
  public function getAverageRating();

  /**
   * Returns false if boarding password does not required and
   * true in other case.
   *
   * @return bool
   */
  public function isBoardingPassRequired();

  /**
   * Returns false if electron ticket does not available on the train and
   * true in other case.
   *
   * @return bool
   */
  public function isEticketAvailable();

  /**
   * Sets the condition of boarding password required on this train.
   *
   * @param bool $boarding_pass_required
   * @return static
   */
  public function setBoardingPassRequired(bool $boarding_pass_required);

  /**
   * Gets formatted message from the train entity.
   *
   * @return string|null
   */
  public function getMessage();

}
