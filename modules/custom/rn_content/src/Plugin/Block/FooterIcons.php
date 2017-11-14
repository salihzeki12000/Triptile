<?php

namespace Drupal\rn_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'FooterIcons' block.
 *
 * @Block(
 *  id = "footer_icons",
 *  admin_label = @Translation("Footer icons"),
 * )
 */
class FooterIcons extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $icons = '<div class="tel footer-icon" id="footer-tel"></div>';
    $icons .= '<div class="mail footer-icon" id="footer-mail"></div>';
    $icons .= '<div class="lang footer-icon" id="footerlanguage">' . \Drupal::languageManager()
        ->getCurrentLanguage()
        ->getId() . '</div>';
    $build['footer_icons']['#markup'] = $icons;
    $build['#attached']['library'][] = 'rn_content/slide-block';

    return $build;
  }

}
