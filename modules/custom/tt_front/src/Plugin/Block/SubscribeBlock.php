<?php

/**
 * @file
 * Contains \Drupal\tt_front_search\Plugin\Block\FrontSearchBlock.
 */

namespace Drupal\tt_front\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * @Block(
 *   id = "subscribe_block",
 *   admin_label = @Translation("Suggest Our Program"),
 * )
 */
class SubscribeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array('#theme' => 'front_subscribe');
  }

}