<?php

namespace Drupal\train_provider;

use Drupal\Core\Form\FormStateInterface;

trait OrderDepthCacheLifetimeFormTrait {

  /**
   * Get list of cache for current train provider.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function getCacheSettingsForm(array &$form, FormStateInterface $form_state) {
    $cache = isset($this->configuration['cache']) ?
      $this->configuration['cache'] : [];
    $cacheNumber = $form_state->get('num_cache');
    $form['cache_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('List of cache'),
      '#prefix' => '<div id="cache-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#tree' => true,
    ];
    if (!isset($cacheNumber)) {
      $cacheNumber = count($cache);
      $form_state->set('num_cache', $cacheNumber);
    }
    $form['cache_fieldset']['cache'] = [
      '#type' => 'container',
    ];
    for ($i = 0; $i < $cacheNumber; $i++) {
      $form['cache_fieldset']['cache'][$i] = [
        '#type' => 'container',
        '#markup' => '#' . ($i + 1),
        '#attributes' => [
          'class' => [
            'container-inline',
          ]
        ]
      ];
      $from = $cache[$i]['from'];
      $to = $cache[$i]['to'];
      $lifetime = $cache[$i]['lifetime'] / 60;
      $form['cache_fieldset']['cache'][$i]['from'] = [
        '#type' => 'number',
        '#title' => $this->t('From'),
        '#default_value' => $from,
        '#description' => $this->t('Time in days'),
      ];
      $form['cache_fieldset']['cache'][$i]['to'] = [
        '#type' => 'number',
        '#title' => $this->t('To'),
        '#default_value' => $to,
        '#description' => $this->t('Time in days'),
      ];
      $form['cache_fieldset']['cache'][$i]['lifetime'] = [
        '#type' => 'number',
        '#title' => $this->t('Lifetime'),
        '#default_value' => $lifetime,
        '#description' => $this->t('Lifetime in minutes'),
      ];
    }
    $form['cache_fieldset']['actions']['add_cache'] = [
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#name' => 'add_one_cache',
      '#submit' => [self::class . '::addOneCache'],
      '#ajax' => [
        'callback' => self::class . '::addmoreCallbackCache',
        'wrapper' => 'cache-fieldset-wrapper',
      ],
    ];
    if ($cacheNumber > 0) {
      $form['cache_fieldset']['actions']['remove_cache'] = [
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#name' => 'remove_one_cache',
        '#submit' => [self::class . '::removeCallbackCache'],
        '#ajax' => [
          'callback' => self::class . '::addmoreCallbackCache',
          'wrapper' => 'cache-fieldset-wrapper',
        ],
      ];
    }
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the stations in it.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return mixed
   */
  static public function addmoreCallbackCache(array &$form, FormStateInterface $form_state) {
    return $form['cache_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  static public function addOneCache(array &$form, FormStateInterface $form_state) {
    $routeField = $form_state->get('num_cache');
    $addButton = $routeField + 1;
    $form_state->set('num_cache', $addButton);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  static function removeCallbackCache(array &$form, FormStateInterface $form_state) {
    $routeField = $form_state->get('num_cache');
    if ($routeField > 0) {
      $removeButton = $routeField - 1;
      $form_state->set('num_cache', $removeButton);
    }
    $form_state->setRebuild();
  }

}