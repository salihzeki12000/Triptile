<?php

namespace Drupal\rn_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'FooterMail' block.
 *
 * @Block(
 *  id = "footer_mail",
 *  admin_label = @Translation("Footer mail"),
 * )
 */
class FooterMail extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title_field' => $this->t('Talk to us'),
         'field_subtitle' => $this->t('Get travel advice from our experts'),
        ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['field_subtitle'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Subtitle'),
      '#description' => $this->t('Block subtitle'),
      '#default_value' => $this->configuration['field_subtitle'],
      '#weight' => '3',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['field_subtitle'] = $form_state->getValue('field_subtitle');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['footer_mail_field_title']['#markup'] = $this->configuration['title_field'];
    $build['footer_mail_field_subtitle']['#markup'] = $this->configuration['field_subtitle'];
    return $build;
  }

}
