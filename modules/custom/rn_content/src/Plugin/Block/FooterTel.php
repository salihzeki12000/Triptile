<?php

namespace Drupal\rn_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'FooterTel' block.
 *
 * @Block(
 *  id = "footer_tel",
 *  admin_label = @Translation("Footer tel"),
 * )
 */
class FooterTel extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title_field' => $this->t('Talk to us'),
      'field_subtitle' => $this->t('Get travel advice from our experts'),
      'field_contacts' => array(),
    ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    // TODO: save values in fieldset

    $form['#tree'] = TRUE;
    $form['field_subtitle'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Subtitle'),
      '#description' => $this->t('Block subtitle'),
      '#default_value' => $this->configuration['field_subtitle'],
      '#weight' => '3',
    ];

    $form['fields']['contacts'] = array(
      '#type' => 'fieldset',
      '#open' => TRUE,
      '#title' => t('Contacts'),
      '#description' => t('Add country and phone number'),
      '#prefix' => '<div id="contacts-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    );

//    $fields_count = !(empty($this->configuration['fields_contacts'])) ? count($this->configuration['fields_contacts']) ? 0;
//    $max = !(empty($this->configuration['field_contacts'])) ? count($this->configuration['field_contacts']) : 0;
//    $form_state->set('fields_count', $max);

    $max = !is_null($form_state->get('fields_count')) ? $form_state->get('fields_count') : count($this->configuration['field_contacts']);
    $form_state->set('fields_count', $max);

    // Add elements that don't already exist
    for ($delta = 0; $delta <= $max; $delta++) {
      if (!isset($form['fields']['contacts'][$delta])) {
        $element = array(
          '#type' => 'textfield',
          '#title' => t('Country'),
          '#required' => FALSE);
        if (isset($this->configuration['field_contacts'][$delta]['phone'])) {
          $element['#default_value'] = $this->configuration['field_contacts'][$delta]['country'];
        }
        $form['fields']['contacts'][$delta]['country'] = $element;
        $element = array(
          '#type' => 'textfield',
          '#title' => t('Phone'),
          '#required' => FALSE,
          '#suffix' => '<hr />');
        if (isset($this->configuration['field_contacts'][$delta]['phone'])) {
          $element['#default_value'] = $this->configuration['field_contacts'][$delta]['phone'];
        }
        $form['fields']['contacts'][$delta]['phone'] = $element;
      }
    }

    $form['fields']['contacts']['add'] = array(
      '#type' => 'submit',
      '#name' => 'addfield',
      '#value' => t('Add contacts'),
      '#submit' => array(array(get_class(), 'addfieldSubmit')),
      '#ajax' => array(
        'callback' => array(get_class(), 'addfieldCallback'),
        'wrapper' => 'contacts-wrapper',
        'effect' => 'fade',
      ),
    );

    $form['fields']['contacts']['remove'] = array(
      '#type' => 'submit',
      '#name' => 'addfield',
      '#value' => t('Remove contacts'),
      '#submit' => array(array(get_class(), 'removefieldSubmit')),
      '#ajax' => array(
        'callback' => array(get_class(), 'removefieldCallback'),
        'wrapper' => 'contacts-wrapper',
        'effect' => 'fade',
      ),
    );

    return $form;
  }

  /**
   * Ajax submit to add new field.
   */
  public static function addfieldSubmit(array &$form, FormStateInterface
&$form_state) {
    $max = $form_state->get('fields_count') + 1;
    $form_state->set('fields_count',$max);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback to add new field.
   */
  public static function addfieldCallback(array &$form, FormStateInterface
&$form_state) {
    return $form['settings']['fields']['contacts'];
  }

  /**
   * Ajax submit to add new field.
   */
  public static function removefieldSubmit(array &$form, FormStateInterface &$form_state) {
    $max = $form_state->get('fields_count') - 1;
    $form_state->set('fields_count',$max);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback to add new field.
   */
  public static function removefieldCallback(array &$form, FormStateInterface &$form_state) {
    return $form['settings']['fields']['contacts'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['field_subtitle'] = $form_state->getValue('field_subtitle');
    $fields = $form_state->getValue('fields');
    foreach ($fields['contacts'] as $key => $contact) {
      if (empty($contact['country']) && empty($contact['phone'])) {
        unset($fields['contacts'][$key]);
      }
    }
    $this->configuration['field_contacts'] = $fields['contacts'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['footer_tel_field_title']['#markup'] = $this->configuration['title_field'];
    $build['footer_tel_field_subtitle']['#markup'] = $this->configuration['field_subtitle'];
    $contacts = $this->configuration['field_contacts'];
    if(!empty($contacts)) {
      $build['footer_contacts']['country'] = array();
      $build['footer_contacts']['tel'] = array();

      foreach ($contacts as $contact) {
        if(!empty($contact['country']) && !empty($contact['phone'])) {
          $build['footer_contacts']['country'][]['#markup'] = $contact['country'];
          $build['footer_contacts']['tel'][]['#markup'] = $contact['phone'];
        }
      }
    }

    return $build;
  }

}
