<?php

namespace Drupal\bene_train_provider\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class LogForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bene_train_provider_log';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $files = file_scan_directory('public://bene_train_provider', '/.*/');
    ksort($files);
    $options = [];
    foreach ($files as $file) {
      $options[$file->filename] = ['file' => Link::fromTextAndUrl($file->filename, Url::fromUri(file_create_url($file->uri)))->toString()];
    }

    $form['files'] = [
      '#type' => 'tableselect',
      '#header' => ['file' => $this->t('Files')],
      '#options' => $options,
      '#empty' => $this->t('No log file found.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete files')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $count = 0;
    foreach ($form_state->getValue('files') as $filename => $delete) {
      if ($delete) {
        file_unmanaged_delete('public://bene_train_provider/' . $filename);
        $count++;
      }
    }

    drupal_set_message($this->formatPlural($count, '1 file has been deleted.', '@count files have been deleted.'));
  }

}
