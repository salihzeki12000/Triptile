<?php

namespace Drupal\rn_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'SocialIcons' block.
 *
 * @Block(
 *  id = "social_icons",
 *  admin_label = @Translation("Social icons"),
 * )
 */
class SocialIconsBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'facebook' => '',
      'twitter' => '',
      'google' => '',
    ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['facebook'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook'),
      '#description' => $this->t('Facebook url'),
      '#default_value' => $this->configuration['facebook'],
      '#weight' => '1',
    ];

    $form['twitter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter'),
      '#description' => $this->t('Twitter url'),
      '#default_value' => $this->configuration['twitter'],
      '#weight' => '2',
    ];

    $form['google'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google+'),
      '#description' => $this->t('Google+ url'),
      '#default_value' => $this->configuration['google'],
      '#weight' => '3',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['facebook'] = $form_state->getValue('facebook');
    $this->configuration['twitter'] = $form_state->getValue('twitter');
    $this->configuration['google'] = $form_state->getValue('google');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if (!empty ($this->configuration['facebook'])) {
      $build['facebook'] = $this->generateLink($this->configuration['facebook']);
    }
    if (!empty ($this->configuration['twitter'])) {
      $build['twitter'] = $this->generateLink($this->configuration['twitter']);
    }
    if (!empty ($this->configuration['google'])) {
      $build['google'] = $this->generateLink($this->configuration['google']);
    }
    return $build;
  }

  protected function generateLink($url) {
    return Link::fromTextAndUrl(' ', Url::fromUri($url, array('attributes' => array('target' => '_blank'))))->toRenderable();
  }

}
