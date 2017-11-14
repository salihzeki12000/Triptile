<?php

namespace Drupal\train_booking;

/**
 * Interface RenderHelperInterface.
 *
 * @package Drupal\train_booking
 */
interface RenderHelperInterface {

  /**
   * Gets gender first letter
   *
   * @param $gender
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|void
   */
  public function getGenderFirstLetter($gender);

  /**
   * Gets title
   *
   * @param $title
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|void
   */
  public function getTitleText($title);

  /**
   * Gets array of departure and arrival date and time values
   *
   * @param $departureDateTime
   * @param $arrivalDateTime
   * @return array
   */
  public function getFullDepartureArrivalDates($departureDateTime, $arrivalDateTime);

}

