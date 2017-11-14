<?php

namespace Drupal\master\Entity;

trait CountableStatEntityTrait {

  /**
   * Gets count of success searches.
   *
   * @return int
   */
  public function getCount() {
    return (int) $this->get('count')->value;
  }

  /**
   * Increases count of success searches.
   *
   * @param int $i
   * @return static
   */
  public function incrementCount($i = 1) {
    $this->set('count', $this->getCount() + $i);
    return $this;
  }

}
