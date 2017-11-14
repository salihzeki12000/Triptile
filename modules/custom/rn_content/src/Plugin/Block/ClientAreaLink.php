<?php

namespace Drupal\rn_content\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a 'ToSearch' block.
 *
 * @Block(
 *  id = "clientarealink",
 *  admin_label = @Translation("Client area link"),
 * )
 */
class ClientAreaLink extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
     'button_label' => $this->t('Your orders'),
    ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label'),
      '#description' => $this->t(''),
      '#default_value' => $this->configuration['button_label'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['button_label'] = $form_state->getValue('button_label');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // TODO: change link

    $url = Url::fromRoute('user.login');
    $link_options = array(
      'attributes' => array(
        'class' => array(
          'sign-in-btn',
        ),
      ),
    );
    $url->setOptions($link_options);
    $build['orders_button_label']['#markup'] = '<div class="mobile-btn ca-btn">'
      . \Drupal::l($this->configuration['button_label'] , $url) .'</div>';

    return $build;
  }

}
