<?php

/**
 * @file
 * Contains \Drupal\tt_front\Plugin\Block\FrontSearchBlock.
 */

namespace Drupal\tt_front\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * @Block(
 *   id = "front_search_block",
 *   admin_label = @Translation("Front search block"),
 * )
 */
class FrontSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    return array('#theme' => 'front_search', '#language' => $language);
  }

}