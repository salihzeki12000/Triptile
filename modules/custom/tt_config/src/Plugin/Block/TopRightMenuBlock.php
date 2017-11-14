<?php

/**
 * @file
 * Contains \Drupal\tt_config\Plugin\Block\TopRightMenuBlock.
 */

namespace Drupal\tt_config\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * @Block(
 *   id = "top_right_menu_block",
 *   admin_label = @Translation("Top right menu"),
 * )
 */
class TopRightMenuBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array('#theme' => 'top_right_menu_block');
  }

}