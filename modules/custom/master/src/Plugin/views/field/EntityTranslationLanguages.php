<?php

namespace Drupal\master\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Class EntityTranslationsLanguages
 *
 * @ViewsField("master_translation_languages")
 * // @todo Can we use general class for all computed fields?
 */
class EntityTranslationLanguages extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    return $this->getTranslationsLanguage($entity);
  }

  protected function getTranslationsLanguage($entity) {
    $languages = [];
    foreach (\Drupal::languageManager()->getLanguages() as $language) {
      if ($entity->hasTranslation($language->getId())) {
        $languages[] = $language->getName();
      }
    }
    $output = [
      '#theme' => 'item_list',
      '#items' => $languages,
    ];
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing since the field is computed.
  }

}
