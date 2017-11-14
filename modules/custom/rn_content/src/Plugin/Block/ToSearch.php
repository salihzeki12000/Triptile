<?php

namespace Drupal\rn_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'ToSearch' block.
 *
 * @Block(
 *  id = "to_search",
 *  admin_label = @Translation("Search train"),
 * )
 */
class ToSearch extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
         'button_label' => $this->t('Search train'),
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
    $build['to_search_button_label']['#markup'] = '<div class="to-search-btn mobile-btn to-top">' . $this->configuration['button_label'] . '</div>';
    $build['#attached']['library'][] = 'rn_content/to-top';
    return $build;
  }

}
