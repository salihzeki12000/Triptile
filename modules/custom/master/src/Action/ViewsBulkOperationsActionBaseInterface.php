<?php

namespace Drupal\master\Action;

interface ViewsBulkOperationsActionBaseInterface {

  /**
   * Returns entity bundle.
   *
   * @return string
   */
  public function getEntityBundle();

  /**
   * Returns the field, which will be processed.
   *
   * @return string
   */
  public function getFieldName();

}

