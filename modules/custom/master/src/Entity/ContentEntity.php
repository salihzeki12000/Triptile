<?php

namespace Drupal\master\Entity;

use Drupal\Core\Entity\ContentEntityBase;

abstract class ContentEntity extends ContentEntityBase {

  /**
   * Gets translation of a field.
   *
   * @param string $field
   * @return \Drupal\Core\Field\FieldItemListInterface
   */
  protected function getTranslated($field) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if ($this->hasTranslation($language)) {
      return $this->getTranslation($language)->get($field);
    }
    else {
      return $this->get($field);
    }
  }

}
