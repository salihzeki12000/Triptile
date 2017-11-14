<?php

namespace Drupal\master\Plugin\FieldFormType;

interface FieldFormTypeWithSummaryInterface {

  /**
   * Gets the summary of the form.
   *
   * @param array $submittedData
   * @return string
   */
  public function getSummary($submittedData);

}