<?php

namespace Drupal\rn_content\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'FooterLanguage' block.
 *
 * @Block(
 *  id = "languageswitcherblock",
 *  admin_label = @Translation("Language switcher block"),
 * )
 */
class LanguageSwitcherBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $block = Block::load('languageswitcher');
    $output = \Drupal::entityTypeManager()
      ->getViewBuilder('block')
      ->view($block);

    $build['custom_language_switcher'] = $output;
    $build['#cache']['tags'][] = 'config:configurable_language_list';

    return $build;
  }

}
