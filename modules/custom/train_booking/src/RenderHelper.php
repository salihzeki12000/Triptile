<?php

namespace Drupal\train_booking;

class RenderHelper implements RenderHelperInterface {

  /**
   * Gets gender first letter
   *
   * @param string $gender
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|void
   */
  public function getGenderFirstLetter($gender) {
    return !empty($gender) ? $gender == 'male' ? t('M') : t('F') : '';
  }

  /**
   * Gets title
   *
   * @param $title
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|void
   */
  public function getTitleText($title) {
    switch ($title) {
      case 'mr':
        return t('Mr.');
      case 'mrs':
        return t('Mrs.');
      case 'miss':
        return t('Miss.');
      default:
        return '';
    }
  }

  /**
   * Gets array of departure and arrival date and time values
   *
   * @param $departureDateTime
   * @param $arrivalDateTime
   * @return array
   */
  public function getFullDepartureArrivalDates($departureDateTime, $arrivalDateTime) {
    $values = [];
    if ($departureDateTime->format('n') == $arrivalDateTime->format('n')) {
      if ($departureDateTime->format('Y') == $arrivalDateTime->format('Y')) {
        $values['departure_arrival_dates'] = $departureDateTime->format('M') . ' ';
        if ($departureDateTime->format('j') == $arrivalDateTime->format('j')) {
          $values['departure_arrival_dates'] .= $departureDateTime->format('j');
          $values['departure_arrival_weekdays'] = $departureDateTime->format('l');
        }
        else {
          $values['departure_arrival_dates'] .= $departureDateTime->format('j') . '-' . $arrivalDateTime->format('j');
          $values['departure_arrival_weekdays'] = $departureDateTime->format('l') . ' - ' . $arrivalDateTime->format('l');
        }
        $values['departure_arrival_dates'] .= ', ' . $departureDateTime->format('Y');
      }
      else {
        $values['departure_date'] = $departureDateTime->format('M j, Y');
        $values['arrival_date'] = $arrivalDateTime->format('M j, Y');
      }
    }
    else {
      if ($departureDateTime->format('Y') == $arrivalDateTime->format('Y')) {
        $values['departure_date'] = $departureDateTime->format('M j, Y');
        $values['arrival_date'] = $arrivalDateTime->format('M j, Y');
        $values['departure_date_month'] = $departureDateTime->format('M j');
        $values['arrival_date_month'] = $arrivalDateTime->format('M j');
        $values['departure_arrival_year'] = $arrivalDateTime->format('Y');
      }
      else {
        $values['departure_date'] = $departureDateTime->format('M j, Y');
        $values['arrival_date'] = $arrivalDateTime->format('M j, Y');
      }
    }
    $values['departure_time'] = $departureDateTime->format('H:i');
    $values['arrival_time'] = $arrivalDateTime->format('H:i');
    return $values;
  }
}