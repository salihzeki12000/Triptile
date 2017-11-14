<?php

namespace Drupal\master;

interface EntityWithDataPropertyInterface {

  /**
   * Gets a value stored in data field with key.
   *
   * @param string $key
   * @return mixed
   */
  public function getData($key);

  /**
   * Adds a data to data field.
   *
   * @param string $key
   * @param mixed $value
   * @return static
   */
  public function setData($key, $value);

  /**
   * Removes all data from data field.
   *
   * @param string $key
   * @return static
   */
  public function removeData($key);

}