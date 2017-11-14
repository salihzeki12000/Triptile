<?php

namespace Drupal\rn_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rn_user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Base class for simple text blocks in client area.
 */
class ClientAreaTextBlock extends BlockBase {
  
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['client_area_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Overview text'),
      '#description' => $this->t('Text for client area overview'),
      '#default_value' => $this->configuration['client_area_text']['value'],
      '#format' => $this->configuration['client_area_text']['format'],
      '#weight' => 0,
    ];
    $form['client_area_text']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user'],
      '#show_restricted' => TRUE,
      '#show_nested' => FALSE,
      '#weight' => 90,
    ];
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['client_area_text'] = $form_state->getValue('client_area_text');
  }
  
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    /* @var \Drupal\rn_user\Entity\User $user */
    if (!\Drupal::currentUser()->isAnonymous() && $user = \Drupal::request()->get('user')) {
      if (!$user instanceof UserInterface) {
        $user = User::load($user);
      }

      $text = $this->configuration['client_area_text'];
      $token_service = \Drupal::token();
      $client_area_text = $token_service->replace($text['value'], ['user' => $user]);
      $build['#markup'] = check_markup($client_area_text, $text['format']);
      $build['#attributes']['class'][] = 'user-text-block';
      $build['#cache'] = [
        'tags' => $user->getCacheTags(),
        'contexts' => ['url.path']
      ];
    }
    return $build;
  }
  
}
