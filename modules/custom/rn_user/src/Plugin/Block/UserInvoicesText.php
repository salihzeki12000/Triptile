<?php

namespace Drupal\rn_user\Plugin\Block;

/**
 * Provides a 'UserInvoicesText' block.
 *
 * @Block(
 *  id = "userinvoicestext",
 *  admin_label = @Translation("User invoices text"),
 * )
 */
class UserInvoicesText extends ClientAreaTextBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'client_area_text' => [
        'value' => 'This page shows all of your invoices, paid and unpaid. Click on the invoice to pay it or download receipt.',
        'format' => 'full_html'
      ],
    ] + parent::defaultConfiguration();
 }
}
