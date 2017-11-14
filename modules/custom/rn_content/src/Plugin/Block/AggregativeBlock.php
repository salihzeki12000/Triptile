<?php

namespace Drupal\rn_content\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 * @Block(
 *  id = "aggregativeblock",
 *  admin_label = @Translation("Aggregative block"),
 * )
 */
class AggregativeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'include_regions' => array(),
      'include_blocks' => array(),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $entity = $form_state->getFormObject()->getEntity();
    $regions = $this->getRegions($entity->getTheme());
    if (!empty($regions)) {
      $form['include_regions'] = array(
        '#title' => t('Include regions'),
        '#type' => 'checkboxes',
        '#description' => t('Select the regions to include in block.'),
        '#default_value' => $this->configuration['include_regions'],
        '#options' => $regions,
        '#required' => true,
      );
      foreach ($regions as $region => $value) {
        $blocks = $this->getBlocksByRegion($region, $entity);
        if(!empty($blocks)) {
          foreach($blocks as $key => $block) {
            if (preg_match('/[\'^£.$%&*()}{@#~?><>,|=+¬-]/', $key)) {
              $blocks[$this->clean($key)] = $block;
              unset($blocks[$key]);
            }
          }
          $selected_values = array_filter($this->configuration['include_blocks'][$region]);
          $default_values = !empty($selected_values) ? array_keys($selected_values) : array();
          $form[$region] = array(
            '#title' => t('Include blocks'),
            '#type' => 'checkboxes',
            '#options' => $blocks,
            '#default_value' => $default_values,
            '#states' => array(
              'visible' => array(
                ':input[name="settings[include_regions][' . $region . ']"]' => array('checked' => TRUE),
              ),
              'invisible' => array(
                ':input[name="settings[include_regions][' . $region . ']"]' => array('checked' => FALSE),
              ),
            ),
          );
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $entity = $form_state->getFormObject()->getEntity();
    $regions = $this->getRegions($entity->getTheme());
    foreach ($regions as $region => $value) {
      $blocks = $form_state->getValue($region);
      if(!empty($blocks)) {
        foreach ($blocks as $key => $block) {
          $this->configuration['include_blocks'][$region][$key] = $block;
        }
      }
    }
    $this->configuration['include_regions'] = $form_state->getValue('include_regions');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $weight = 0;
    $include_blocks = $this->configuration['include_blocks'];

    foreach($include_blocks as $region => $blocks) {
      if(!empty($blocks)) {
        $block_ids = array_filter($blocks);
      }

      if (!empty($block_ids)) {
        foreach ($block_ids as $block_id) {
          $block = \Drupal\block\Entity\Block::load($block_id);

          $output = \Drupal::entityTypeManager()
            ->getViewBuilder('block')
            ->view($block);

          $build[$block_id] = $output;
          $build[$block_id]['#weight'] = $weight++;
        }

      }
    }

    $build['#attributes']['class'][] = 'mobile-menu-block';
    $build['#attributes']['class'][] = 'aggregative-block';
    $build['#attached']['library'][] = 'rn_content/dropdown-menu';

    return $build;
  }

  protected function getRegions($theme) {
    return system_region_list($theme);
  }

  protected function getBlocksByRegion($region, $entity) {
    $query = \Drupal::entityQuery('block');
    $blocks = $query
      ->condition('region', $region)
      ->condition('theme', $entity->getTheme())
      ->execute();

    if($key = array_search($entity->getPluginId(), $blocks)) {
      unset($blocks[$key]);
    }

    return $blocks;
  }

  protected function clean($string) {
    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
  }

}


