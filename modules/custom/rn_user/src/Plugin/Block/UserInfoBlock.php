<?php

namespace Drupal\rn_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\block\Entity\Block;
use Drupal\Core\Url;
use \Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserInfoBlock' block.
 *
 * @Block(
 *  id = "userinfoblock",
 *  admin_label = @Translation("User info block"),
 * )
 */
class UserInfoBlock extends BlockBase {

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

      $build['user_info'] = [
        'first_name' => [
          'label' => ['#markup' => $this->t('First name:')],
          'value' => [
            '#markup' => $user->getFirstName(),
            '#cache' => [
              'tags' => $user->getCacheTags(),
              'contexts' => ['url.path'],
            ],
          ],
        ],
        'last_name' => [
          'label' => ['#markup' => $this->t('Last name:')],
          'value' => [
            '#markup' => $user->getLastName(),
            '#cache' => [
              'tags' => $user->getCacheTags(),
              'contexts' => ['url.path'],
            ],
          ],
        ],
        'phone' => [
          'label' => ['#markup' => $this->t('Your phone:')],
          'value' => [
            '#markup' => $user->getPhoneNumber(),
            '#cache' => [
              'tags' => $user->getCacheTags(),
              'contexts' => ['url.path'],
            ],
          ],
        ],
      ];

      $url = Url::fromRoute('entity.user.edit_form', ['user' => $user->id()]);
      $link_options = ['attributes' => ['class' => ['profile-edit-link']]];
      $url->setOptions($link_options);
      $build['link_to_profile']['#markup'] = \Drupal::l($this->t('Edit profile'), $url);
      $build['link_to_profile']['#cache'] = [
        'tags' => $user->getCacheTags(),
        'contexts' => ['url.path'],
      ];
    }

    return $build;
  }

}
