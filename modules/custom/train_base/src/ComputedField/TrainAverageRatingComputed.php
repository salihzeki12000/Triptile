<?php

namespace Drupal\train_base\ComputedField;

use Drupal\Core\Field\FieldItemList;

class TrainAverageRatingComputed extends FieldItemList {

  public function getValue($include_computed = FALSE) {
    /** @var \Drupal\train_base\Entity\TrainInterface $train */
    $train = $this->getEntity();
    if ($train->getInternalRating() == 0) {
      return $train->getTPRating();
    }
    elseif ($train->getTPRating() == 0) {
      return $train->getInternalRating();
    }
    else {
      return ($train->getInternalRating() + $train->getTPRating()) / 2;
    }
  }

}
