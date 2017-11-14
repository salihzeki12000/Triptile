<?php

namespace Drupal\rn_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'FooterMail' block.
 *
 * @Block(
 *  id = "searchformawards",
 *  admin_label = @Translation("Search form awards"),
 * )
 */
class SearchFormAwards extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'field_awards' => array('value' => '', 'format' => 'full_html'),
      ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['field_awards'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Awards'),
      '#description' => $this->t('Awards for the search form on homepage'),
      '#default_value' => check_markup($this->configuration['field_awards']['value']),
      '#format' => $this->configuration['field_awards']['format'],
      '#weight' => 0,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['field_awards'] = $form_state->getValue('field_awards');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $awards = $this->configuration['field_awards'];
    $build['field_awards']['#markup'] = check_markup($awards['value'], $awards['format']);
    return $build;
  }

}
