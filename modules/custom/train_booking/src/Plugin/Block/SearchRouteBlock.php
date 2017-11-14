<?php

namespace Drupal\train_booking\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal;

/**
 * Provides a 'SearchRouteBlock' block.
 *
 * @Block(
 *   id = "search_route_block",
 *   admin_label = @Translation("Search route block"),
 * )
 */

class SearchRouteBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['#type'] = 'container';
    $build['#cache'] = ['max-age' => 0];
    $fid = $this->configuration['image'];
    if (!empty($fid)) {
      /** @var \Drupal\file\Entity\File $file */
      $file = Drupal::entityTypeManager()->getStorage('file')->load($fid);
      if(!empty($file)) {
        $url = $file->url();
        $build['#attributes']['style'] = 'background-image: url(' . $url . ')';
      }
    }

    $build['#attributes']['class'][] = 'train-search-form-block';
    $build['search_form'] = \Drupal::formBuilder()->getForm
    ('Drupal\train_booking\Form\SearchForm');

    return $build;
  }

  public function defaultConfiguration() {
    return [
      'image' => $this->configuration['image'],
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
      '#title' => 'Background image',
      '#description' => 'Will be show like a background image of the block.',
      '#type' => 'managed_file',
      '#default_value' => [$this->configuration['image']],
      '#upload_location' => 'public://upload/search_block/'
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
      $file_usage->add($file, 'train_booking', 'block', $this->getPluginId());
    }
  }

}