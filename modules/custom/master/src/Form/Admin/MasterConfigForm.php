<?php

namespace Drupal\master\Form\Admin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class MasterConfigForm extends ConfigForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'master_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->configFactory->get('master.settings');

    // Record translations.
    $record_translations = $config->get('record_translations');
    $form['record_translations_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Record translations to the files'),
    ];
    $form['record_translations_fieldset']['record_translations'] = [
      '#title' => $this->t('On/Off'),
      '#type' => 'checkbox',
      '#default_value' => !empty($record_translations) ? $record_translations : null,
    ];

    // Reset translation files.
    $form['reset_translation_files_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Translation files management'),
    ];
    $form['reset_translation_files_fieldset']['reset_translation_files'] = [
      '#title' => $this->t('Reset files content'),
      '#type' => 'checkbox',
    ];

    $form['links_fieldset']  = [
      '#type' => 'fieldset',
      '#title' => $this->t('Translation export files'),
    ];

    $files = file_scan_directory('public://translations_export', '/.*/');
    foreach ($files as $file) {
      $form['links_fieldset'][$file->filename] = [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        '#title' => $file->filename,
        '#type' => 'link',
        '#url' => Url::fromUri(file_create_url($file->uri)),
      ];

    }


    // Actions.
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('master.settings');

    $record_translations = $form_state->getValue($form['record_translations_fieldset']['record_translations']['#parents']);
    $config->set('record_translations', $record_translations);

    $reset_translation_files = $form_state->getValue($form['reset_translation_files_fieldset']['reset_translation_files']['#parents']);
    if ($reset_translation_files) {
      $folderPath = 'public://translations_export/';
      if (file_prepare_directory($folderPath, FILE_CREATE_DIRECTORY)) {
        $template_path = drupal_get_path('module', 'master') . '/templates/translations_export/translations.po';
        file_unmanaged_copy($template_path, $folderPath . 'translations.po', FILE_EXISTS_REPLACE);
        $template_path = drupal_get_path('module', 'master') . '/templates/translations_export/translations.html';
        file_unmanaged_copy($template_path, $folderPath . 'translations.html', FILE_EXISTS_REPLACE);
      }
    }
    $config->save();

  }
}