<?php

namespace Drupal\rn_content\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'ToSearch' block.
 *
 * @Block(
 *  id = "mobilemenublock",
 *  admin_label = @Translation("Mobile menu block"),
 * )
 */
class MobileMenuBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $current_block = \Drupal::entityQuery('block')
      ->condition('plugin', $this->getPluginId())
      ->execute();

    $query = \Drupal::entityQuery('block');

    $group = $query->orConditionGroup()
      ->condition('region', 'header_first')
      ->condition('region', 'header_third');

    $blocks = $query->condition($group)
      ->condition('theme', \Drupal::theme()->getActiveTheme()->getName())
      ->execute();

    if(!empty($current_block)) {
      $common_blocks = array_intersect($current_block, $blocks);

      foreach($common_blocks as $common_block) {
        if(($key = array_search($common_block, $blocks)) !== false) {
          unset($blocks[$key]);
        }
      }
    }

    $weight = 0;

    foreach($blocks as $block) {
      $mobile_menu_block = \Drupal\block\Entity\Block::load($block);
      $output = \Drupal::entityTypeManager()
        ->getViewBuilder('block')
        ->view($mobile_menu_block);

      $build[$block] = $output;
      $build[$block]['#weight'] = $weight++;
    }

    $build['#attributes']['class'][] = 'mobile-menu-block';
    $build['#attached']['library'][] = 'rn_content/dropdown-menu';

    return $build;
  }

}
