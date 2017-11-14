<?php

namespace Drupal\store\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\store\DefaultCurrency;

/**
 * Provides a 'CurrencyList' block.
 *
 * @Block(
 *  id = "currency_list",
 *  admin_label = @Translation("Currency list"),
 * )
 */
class CurrencyList extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\store\DefaultCurrency $default_currency
   * @internal param \Drupal\Core\Url $url
   * @internal param \Drupal\currency\FormHelper $currency_form_helper
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    DefaultCurrency $default_currency
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currencyStorage = $entity_type_manager->getStorage('currency');
    $this->defaultCurrency = $default_currency;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('store.default_currency')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['currency_list']['#prefix'] = '<div class="currency-list">';
    $build['currency_list']['#suffix'] = '</div>';
    $build['#cache']['tags'][] = 'config:currency_list';
    $build['#cache']['contexts'][] = 'cookies:user_currency';

    $currencies = $this->currencyStorage->loadMultiple();
    $active_currency = $this->defaultCurrency->getUserCurrency();
    $visible_currencies = \Drupal::config('store.settings')->get('visible_currencies');

    $current_url = Url::fromRoute('<current>')->toString();

    foreach($currencies as $currency_id => $currency) {
      if ($currency->status() && $visible_currencies[$currency->currencyCode]) {
        $url = Url::fromRoute('store.currency.switch_currency',
          ['currency' => $currency->getCurrencyCode()],
          ['query' => ['destination' => $current_url]]
        );

        $item_class = (!empty($active_currency) && $active_currency == $currency->getCurrencyCode()) ? 'active' : 'inactive';

        $currency_list = '<div class="currency-item ' . $item_class . '">';
        $currency_list .= '<div class="sign">' . Link::fromTextAndUrl($currency->getSign(), $url)->toString() . '</div>';
        $currency_list .= '<div class="label">' . Link::fromTextAndUrl($currency->getLabel(), $url)->toString() . '</div>';
        $currency_list .= '</div>';

        $build['currency_list'][$currency->getCurrencyCode()]['#markup'] = $currency_list;
      }
    }

    return $build;
  }

}
