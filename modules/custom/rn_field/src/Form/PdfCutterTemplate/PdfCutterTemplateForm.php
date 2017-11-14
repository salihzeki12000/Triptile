<?php

namespace Drupal\rn_field\Form\PdfCutterTemplate;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PdfCutterTemplateForm.
 *
 * @property \Drupal\rn_field\Entity\PdfCutterTemplate entity
 *
 * @package Drupal\rn_field\Form
 */
class PdfCutterTemplateForm extends EntityForm {
  
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    
    $entity = $this->entity;
    
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Name for the pdf cutter template.'),
      '#required' => true,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\rn_field\Entity\PdfCutterTemplate::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['canvases'] = array(
      '#type' => 'table',
      '#header' => [
        $this->t('Canvas #'),
        $this->t('Page #'),
        $this->t('X coordinate'),
        $this->t('Y coordinate'),
        $this->t('Width'),
        $this->t('Height'),
        $this->t('Color'),
        $this->t('Action'),
      ],
      '#attributes' => ['class' => ['canvases-table']],
    );

    $clickedButton = $form_state->getTriggeringElement();
    $canvases = $clickedButton ? $form_state->getValues()['canvases'] : $entity->getCanvases();
    $exclude = $clickedButton && strpos($clickedButton['#name'], 'remove') !== false ? str_replace('remove-', '', $clickedButton['#name']) : -1;

    foreach ($canvases as $i => $canvas) {
      if ($i != $exclude) {
        $form['canvases'][] = $this->getCanvasForm($i, $canvas);
      }
    }

    if (!$clickedButton || strpos($clickedButton['#name'], 'remove') === false) {
      $i = isset($i) ? $i + 1 : 0;
      $form['canvases'][] = $this->getCanvasForm($i);
    }

    $form['add_more'] = [
      '#type' => 'button',
      '#name' => 'add_more',
      '#value' => $this->t('Add more'),
      '#ajax' => [
        'callback' => [$this, 'ajaxRefreshTable'],
        'progress' => ['type' => 'none'],
      ],
      '#attributes' => ['class' => ['add-more-canvas-btn']],
      '#limit_validation_errors' => [['canvases']],
    ];

    $form['#attached']['library'][] = 'rn_field/pdf-cutter-template';
  
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $formState) {
    $entity = $this->entity;
    
    switch ($entity->save()) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label pdf canvas.', [
          '%label' => $entity->label(),
        ]));
        break;
      
      default:
        drupal_set_message($this->t('Saved the %label pdf cutter template.', [
          '%label' => $entity->label(),
        ]));
    }
    
    $formState->setRedirectUrl($entity->urlInfo('collection'));
  }

  /**
   * Creates ajax response to replace table with canvases.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function ajaxRefreshTable($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('.canvases-table', $form['canvases']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $canvases = [];
    foreach ($values['canvases'] as $canvas) {
      foreach ($canvas as $key => $value) {
        if ($value === '') {
          continue 2;
        }
      }
      $canvases[] = $canvas;
    }
    $values['canvases'] = $canvases;

    foreach ($values as $key => $value) {
      $entity->set($key, $value);
    }
  }

  /**
   * Generates form for a canvas.
   *
   * @param int $i
   * @param array $canvas
   *
   * @return array
   */
  protected function getCanvasForm($i, array $canvas = []) {
    $form['i'] = ['#markup' => $i + 1];
    $form['page'] = [
      '#step' => 1,
      '#type' => 'number',
      '#default_value' => $canvas['page'] ?? 1,
    ];
    $form['x'] = [
      '#type' => 'number',
      '#default_value' => $canvas['x'] ?? '',
    ];
    $form['y'] = [
      '#type' => 'number',
      '#default_value' => $canvas['y'] ?? '',
    ];
    $form['width'] = [
      '#type' => 'number',
      '#default_value' => $canvas['width'] ?? '',
    ];
    $form['height'] = [
      '#type' => 'number',
      '#default_value' => $canvas['height'] ?? '',
    ];
    $form['color'] = [
      '#type' => 'textfield',
      '#default_value' => $canvas['color'] ?? '#ffffff',
      '#size' => 10,
    ];
    $form['remove'] = [
      '#type' => 'button',
      '#name' => 'remove-' . $i,
      '#value' => $this->t('Remove'),
      '#ajax' => [
        'callback' => [$this, 'ajaxRefreshTable'],
        'progress' => ['type' => 'none'],
      ],
      '#limit_validation_errors' => [['canvases']],
    ];

    return $form;
  }
  
}
