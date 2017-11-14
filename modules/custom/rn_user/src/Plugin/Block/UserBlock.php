<?php

namespace Drupal\rn_user\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\store\DefaultCurrency;

/**
 * Provides a 'UserBlock' block.
 *
 * @Block(
 *  id = "user_block",
 *  admin_label = @Translation("User block"),
 * )
 */
class UserBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The Default Currency service.
   *
   * @var \Drupal\store\DefaultCurrency
   */
  protected $defaultCurrency;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   * @param \Drupal\store\DefaultCurrency $default_currency
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxy $current_user, DefaultCurrency $default_currency) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->defaultCurrency = $default_currency;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('current_user'), $container->get('store.default_currency'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $currentLanguageCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $currentCurrencyCode = $this->defaultCurrency->getUserCurrency();
    $user_country_code = \Drupal::service('master.maxmind')->getCountry();
    if (!empty($user_country_code)) {
      // So now we don't need additional cache context based on country code.
      // But keep it in the mind, if something going wrong.
      if ($currentLanguageCode == 'en' && $user_country_code == 'GB') {
        $currentLanguageCode = 'gb';
      }
    }
    $build['user_block_icon']['#theme'] = 'current_lang_currency';
    $build['user_block_icon']['#currentLanguageCode'] = $currentLanguageCode;
    $build['user_block_icon']['#currentCurrencyCode'] = $currentCurrencyCode;
    $build['user_block_icon']['#weight'] = 0;

    $build['user_blocks']['#prefix'] = '<div class="user-blocks ' . $currentLanguageCode . '">';
    $build['user_blocks']['#suffix'] = '</div>';
    $build['user_blocks']['#weight'] = 1;

    $build['user_block_triangle']['#markup'] = '<div class="user-triangle"></div>';
    $build['user_block_icon']['#weight'] = 2;

    $blocks = [
      [
        'id' => 'languageswitcherblock',
        'weight' => 3,
        'title' => $this->t('Language'),
      ],
      [
        'id' => 'currencylist',
        'weight' => 4,
        'title' => $this->t('Currency'),
      ],
    ];

    foreach ($blocks as $block) {
      if (isset($block['id'])) {
        $user_block = Block::load($block['id']);
        if ($user_block) {
          $output = \Drupal::entityTypeManager()
            ->getViewBuilder('block')
            ->view($user_block);

          $build['user_blocks'][$block['id']] = $output;

          if (isset($block['weight'])) {
            $build['user_blocks'][$block['id']]['#weight'] = $block['weight'];
          }
        }
      }
    }

    $build['#cache']['contexts'] = [
      'user_country',
      'user_currency',
    ];
    $build['#attached']['library'][] = 'rn_user/user-block';

    return $build;
  }

}
