<?php

namespace Drupal\rn_content\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'FooterLanguage' block.
 *
 * @Block(
 *  id = "footerlanguage",
 *  admin_label = @Translation("Footer language"),
 * )
 */
class FooterLanguage extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
         'title_field' => $this->t('Language'),
         'field_subtitle' => $this->t('Select your language preferences'),
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
      '#maxlength' => 255,
      '#size' => 255,
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
    $build['footer_language_field_title']['#markup'] = $this->configuration['title_field'];
    $build['footer_language_field_subtitle']['#markup'] = $this->configuration['field_subtitle'];

    $block = Block::load('languageswitcherblock');
    $output = \Drupal::entityTypeManager()
      ->getViewBuilder('block')
      ->view($block);

    $build['footer_drupal_language_switcher'] = $output;

    $build['current_language']['#markup'] = \Drupal::languageManager()
      ->getCurrentLanguage()->getId();
    return $build;
  }

}
