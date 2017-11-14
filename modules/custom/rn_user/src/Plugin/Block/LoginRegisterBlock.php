<?php

namespace Drupal\rn_user\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Login/register block' block.
 *
 * @Block(
 *  id = "login_register_block",
 *  admin_label = @Translation("Login/register block"),
 * )
 */
class LoginRegisterBlock extends BlockBase {

  public function defaultConfiguration() {
    return [
      'image' => NULL,
      'button_label_anonymous' => $this->t('sign in'),
      'button_label_authorized' => $this->t('my account'),
      'button_label_logout' => $this->t('log out'),
    ];
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['image'] = [
      '#title' => $this->t('Background image'),
      '#description' => $this->t('Will be show like a background image of the block.'),
      '#type' => 'managed_file',
      '#default_value' => [$this->configuration['image']],
      '#upload_location' => 'public://upload/login_block/'
    ];
    $form['button_label_anonymous'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label for anonymous'),
      '#default_value' => $this->configuration['button_label_anonymous'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['button_label_authorized'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label for authorized user'),
      '#default_value' => $this->configuration['button_label_authorized'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['button_label_logout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Log out button label'),
      '#default_value' => $this->configuration['button_label_logout'],
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
    $image = $form_state->getValue('image');
    $fid = (isset($image[0])) ? $form_state->getValue('image')[0] : NULL;

    // Deleting old image.
    if ($this->configuration['image'] && $this->configuration['image'] != $fid) {
      $old_file = \Drupal::entityTypeManager()->getStorage('file')->load($this->configuration['image']);
      if ($old_file) {
        $old_file->delete();
      }
    }
    if (empty($fid)) {
      $this->configuration['image'] = NULL;
    }
    else {
      $this->configuration['image'] = $fid;
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
      $file->setPermanent();
      $file->save();
      $file_usage = \Drupal::service('file.usage');
      $file_usage->add($file, 'rn_user', 'block', $this->getPluginId());
    }

    $this->configuration['button_label_anonymous'] = $form_state->getValue('button_label_anonymous');
    $this->configuration['button_label_authorized'] = $form_state->getValue('button_label_authorized');
    $this->configuration['button_label_logout'] = $form_state->getValue('button_label_logout');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    if (\Drupal::currentUser()->isAnonymous()) {
      $btn_inside = $this->configuration['button_label_anonymous'];
      $class = 'logged-out';

      $sign_in_form = \Drupal::formBuilder()->getForm('Drupal\user\Form\UserLoginForm');
      $build['sign_in'] = $sign_in_form;

      $forgot_pass_form = \Drupal::formBuilder()->getForm('Drupal\user\Form\UserPasswordForm');
      $build['forgot_pass'] = $forgot_pass_form;

      $entity = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->create(array());

      $formObject = \Drupal::entityTypeManager()
        ->getFormObject('user', 'register')
        ->setEntity($entity);
      $sign_up_form = \Drupal::formBuilder()->getForm($formObject);

      if (isset($sign_up_form['account']['status'])) {
        $sign_up_form['account']['status']['#access'] = FALSE;
      }

      if (isset($sign_up_form['account']['roles'])) {
        $sign_up_form['account']['roles']['#access'] = FALSE;
      }

      if (isset($sign_up_form['account']['notify'])) {
        $sign_up_form['account']['notify']['#access'] = FALSE;
      }

      $build['sign_up'] = $sign_up_form;
      $fid = $this->configuration['image'];
      /** @var \Drupal\file\Entity\File $file */
      $file = Drupal::entityTypeManager()->getStorage('file')->load($fid);
      if(!empty($file)) {
        $build['image'] = array(
          '#theme' => 'image_style',
          '#style_name' => 'sign_in',
          '#uri' => $file->getFileUri(),
        );
      }

      $build['tab_library']['#attached']['library'][] = 'rn_user/login-register-block';
      $build['popup_button']['#markup'] = '<div class="mobile-btn ca-btn ' . $class . '">' . $btn_inside .'</div>';
    }
    else {
      $build['my_account_buttons'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'mobile-btn',
            'ca-btn',
            'logged-in'
          ]
        ]
      ];
      $build['my_account_buttons']['my_account'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['my-account-btn']]
      ];
      $build['my_account_buttons']['logout'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['logout-btn']]
      ];
      $build['my_account_buttons']['my_account']['link'] = $this->generateUserAccountLink('user.page','button_label_authorized','my-account-link');
      $build['my_account_buttons']['logout']['link'] = $this->generateUserAccountLink('user.logout','button_label_logout','sign-out-link', false);
    }
    return $build;
  }

  /**
   * Returns a link for user account
   * 
   * @param $route_name
   * @param string $link_class
   * @param $button_configuration
   * @param bool|TRUE $show_text
   * @return array|\mixed[]
   */
  protected function generateUserAccountLink($route_name, $button_configuration, $link_class = '', $show_text = true) {
    $url = Url::fromRoute($route_name);
    $link_options = [
      'attributes' => [
        'class' => [$link_class],
        'title' => $this->configuration[$button_configuration]
      ],
    ];
    $url->setOptions($link_options);
    $text = $show_text ? $this->configuration[$button_configuration] : ' ';
    return Link::fromTextAndUrl($text, $url)->toRenderable();
  }

}
