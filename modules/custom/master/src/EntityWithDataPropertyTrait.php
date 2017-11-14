<?php

namespace Drupal\master;

trait EntityWithDataPropertyTrait {

  /**
   * {@inheritdoc}
   */
  public function getData($key) {
    if (!empty($this->get('data')->first()) && !empty($this->get('data')->first()->getValue()[$key])) {
      return $this->get('data')->first()->getValue()[$key];
    }
    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($key, $value) {
    $data = [];
    if (!empty($this->get('data')->first())) {
      $data = $this->get('data')->first()->getValue();
    }
    $data = array_merge($data, [$key => $value]);
    $this->set('data', [$data]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeData($key) {
    if (!empty($this->get('data')->first())) {
      $data = $this->get('data')->first()->getValue();
      unset($data[$key]);
      $this->set('data', [$data]);
    }
    return $this;
  }
}