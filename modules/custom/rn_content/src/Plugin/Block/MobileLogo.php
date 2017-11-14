<?php

namespace Drupal\rn_content\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal;

/**
 * Provides a 'MobileLogo' block.
 *
 * @Block(
 *  id = "mobilelogo",
 *  admin_label = @Translation("Mobile logo"),
 * )
 */
class MobileLogo extends BlockBase {

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
      '#title' => 'Mobile logo',
      '#description' => 'Logo for mobile devices.',
      '#type' => 'managed_file',
      '#default_value' => [$this->configuration['image']],
      '#upload_location' => 'public://upload/project-images/'
    ];
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if($fid = $form_state->getValue('image')[0]) {
      // Delete image if field is empty.
      if (empty($fid) && $this->configuration['image']) {
        $old_file = Drupal::entityTypeManager()->getStorage('file')->load($this->configuration['image']);
        $old_file->delete();
        $this->configuration['image'] = NULL;
      }
      else {
        // Deleting old image.
        if ($this->configuration['image'] && $this->configuration['image'] != $fid) {
          $old_file = Drupal::entityTypeManager()->getStorage('file')->load($this->configuration['image']);
          $old_file->delete();
        }
        // Creating new image.
        if ($this->configuration['image'] != $fid) {
          $this->configuration['image'] = $fid;
          $file = Drupal::entityTypeManager()->getStorage('file')->load($fid);
          $file->setPermanent();
          $file->save();
          $file_usage = \Drupal::service('file.usage');
          $file_usage->add($file, 'train_booking', 'block', $this->getPluginId());
        }
      }
    }


  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = [];

    $fid = $this->configuration['image'];

    if(!empty($fid)) {
      $file = Drupal::entityTypeManager()->getStorage('file')->load($fid);


      $variables = array(
        'style_name' => 'large',
        'uri' => $file->getFileUri(),
      );

      // The image.factory service will check if our image is valid.
      $image = \Drupal::service('image.factory')->get($file->getFileUri());
      if ($image->isValid()) {
        $variables['width'] = $image->getWidth();
        $variables['height'] = $image->getHeight();

        $logo_array = [
          '#theme' => 'image_style',
          '#width' => $variables['width'],
          '#height' => $variables['height'],
          '#style_name' => $variables['style_name'],
          '#uri' => $variables['uri'],
        ];

        $build['logo_image'] = $logo_array;
        $build['#attributes']['class'][] = 'mobile-logo';
      }

    }

    return $build;
  }

}
