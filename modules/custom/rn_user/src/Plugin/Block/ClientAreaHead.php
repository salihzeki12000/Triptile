<?php

namespace Drupal\rn_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\block\Entity\Block;

/**
 * Provides a 'ClientAreaText' block.
 *
 * @Block(
 *  id = "clientareahead",
 *  admin_label = @Translation("Client area head"),
 * )
 */
class ClientAreaHead extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if (!\Drupal::currentUser()->isAnonymous()) {

      switch (\Drupal::service('current_route_match')->getRouteName()) {
        case 'entity.user.canonical':
          $block_id = 'useroverviewtext';
          break;
        case 'view.user_orders.my_orders':
          $block_id = 'userorderstext';
          break;
        case 'view.user_invoices.my_invoices':
          $block_id = 'userinvoicestext';
          break;
      }
      if (isset($block_id)) {
        $block = Block::load($block_id);
        if ($block) {
          $build['text'] = \Drupal::entityTypeManager()
            ->getViewBuilder('block')
            ->view($block);
          $build['text']['#cache']['contexts'][] = 'route.name';
          $user_info_block = Block::load('userinfoblock');
          if ($user_info_block) {
            $build['user_block'] = \Drupal::entityTypeManager()
              ->getViewBuilder('block')
              ->view($user_info_block);
          }
        }
      }
    }

    return $build;
  }

}
