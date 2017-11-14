<?php

namespace Drupal\master;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

class MasterServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    /*$definition = $container->getDefinition('entity.autocomplete_matcher');
    $definition->setClass('Drupal\master\Entity\EntityAutocompleteMatcherCustom');

//    $definition = $container->getDefinition('country_manager');
//    $definition->setClass('Drupal\master\CountryRepository');

    $definition = $container->getDefinition('string_translation');
    $definition
      ->setClass('Drupal\master\TranslationManager')
      ->setArguments([new Reference('language.default'), new Reference('request_stack'), new Reference('config.factory'), new Reference('current_user')]);*/
  }

}
