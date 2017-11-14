<?php

namespace Drupal\payment\Plugin\PaymentMethod;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\plugin\PluginOperationsProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PluginOperationsProvider
 *
 * @package Drupal\payment\Plugin\PaymentMethod
 */
class PluginOperationsProvider implements PluginOperationsProviderInterface, ContainerInjectionInterface  {

  use StringTranslationTrait;

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination.
   */
  public function __construct(TranslationInterface $string_translation, RedirectDestinationInterface $redirect_destination) {
    $this->redirectDestination = $redirect_destination;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('string_translation'), $container->get('redirect.destination'));
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($plugin_id) {
    return [
      'configure' => [
        'title' => $this->t('Configure'),
        'query' => $this->redirectDestination->getAsArray(),
        'url' => new Url('payment.payment_config.payment_methods.configuration', ['payment_method' => $plugin_id]),
      ],
    ];
  }

}
