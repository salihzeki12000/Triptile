<?php

namespace Drupal\rn_user\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Form\FormStateInterface;
use Drupal;

/**
 * Provides a 'Login/register dropdown' block.
 *
 * @Block(
 *  id = "login_register_dropdown",
 *  admin_label = @Translation("Login/register dropdown"),
 * )
 */
class LoginRegisterDropdown extends LoginRegisterBlock {

  public function defaultConfiguration() {
    $defaultConfiguration = parent::defaultConfiguration();
    $defaultConfiguration['button_label_register'] = 'Register';
    return $defaultConfiguration;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['button_label_register'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Register button label'),
      '#default_value' => $this->configuration['button_label_register'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['button_label_register'] = $form_state->getValue('button_label_register');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    if (\Drupal::currentUser()->isAnonymous()) {
      $class = 'logged-out';
      $build['login_button']['#markup'] = '<div class="mobile-btn ' . $class . '" id="sign-in-btn">' . $this->configuration['button_label_anonymous'] .'</div>';
      $build['register_button']['#markup'] = '<div class="mobile-btn ' . $class . '" id="sign-up-btn">' . $this->configuration['button_label_register'] .'</div>';
    }
    else {
      $build['my_account']['link'] = $this->generateUserAccountLink('user.page','button_label_authorized','my-account-link');
      $build['logout']['link'] = $this->generateUserAccountLink('user.logout','button_label_logout','sign-out-link');
    }
    $build['trigger_library']['#attached']['library'][] = 'rn_user/login-register-dropdown';
    return $build;
  }
}
