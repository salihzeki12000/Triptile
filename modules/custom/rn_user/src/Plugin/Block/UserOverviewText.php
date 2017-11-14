<?php

namespace Drupal\rn_user\Plugin\Block;

/**
 * Provides a 'UserOverviewText' block.
 *
 * @Block(
 *  id = "useroverviewtext",
 *  admin_label = @Translation("User overview text"),
 * )
 */
class UserOverviewText extends ClientAreaTextBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'client_area_text' => [
        'value' => '<p>Welcome to your Client Area! This is a secure place where we store all information about your trips with us. Please, make sure to read our policies and FAQs before departure.</p>',
        'format' => 'full_html'
      ],
    ] + parent::defaultConfiguration();
 }
}
