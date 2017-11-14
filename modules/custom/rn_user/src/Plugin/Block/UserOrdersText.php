<?php

namespace Drupal\rn_user\Plugin\Block;

/**
 * Provides a 'UserOrdersText' block.
 *
 * @Block(
 *  id = "userorderstext",
 *  admin_label = @Translation("User orders text"),
 * )
 */
class UserOrdersText extends ClientAreaTextBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'client_area_text' => [
        'value' => 'Welcome to your trips page. Here you can find all of your journeys we have been planning for you.',
        'format' => 'full_html'
      ],
    ] + parent::defaultConfiguration();
 }

}
