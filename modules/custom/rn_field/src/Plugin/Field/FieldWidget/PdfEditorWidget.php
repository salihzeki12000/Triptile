<?php

namespace Drupal\rn_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\master\PdfTool;
use Drupal\rn_field\Entity\PdfCutterTemplate;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'pdf_editor' widget.
 *
 * @FieldWidget(
 *   id = "pdf_editor",
 *   label = @Translation("PDF Editor"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class PdfEditorWidget extends FileWidget {

  /**
   * @var \Drupal\master\PdfTool
   */
  protected $pdfTool;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * PdfEditorWidget constructor.
   *
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param array $third_party_settings
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   * @param \Drupal\master\PdfTool $pdf_tool
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info, PdfTool $pdf_tool, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $element_info);

    $this->pdfTool = $pdf_tool;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('element_info'), $container->get('master.pdf_tool'), $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'available_tools' => [],
        'merge_by_default' => 0,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['available_tools'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Available tools'),
      '#options' => [
        'merge' => $this->t('Merge'),
        'cutter' => $this->t('Cutter'),
      ],
      '#default_value' => $this->getSetting('available_tools'),
    ];

    $form['merge_by_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Merge enabled by default'),
      '#default_value' => $this->getSetting('merge_by_default'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Available tools: @tools', ['@tools' => implode(', ', $this->getSetting('available_tools'))]);
    if (in_array('merge', $this->getSetting('available_tools'))) {
      $merge = $this->getSetting('merge_by_default') ? $this->t('Yes') : $this->t('No');
      $summary[] = $this->t('Merge enabled by default: @merge', ['@merge' => $merge]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Add form fields.
    if (empty($element['#default_value']['fids'])) {
      if (in_array('cutter', $this->getSetting('available_tools'))) {
        $pdfCutterTemplates = $this->entityTypeManager
          ->getStorage('pdf_cutter_template')
          ->loadMultiple();
        $options = [];
        foreach ($pdfCutterTemplates as $id => $pdfCutterTemplate) {
          $options[$id] = $pdfCutterTemplate->label();
        }
        $element['cutter_template'] = [
          '#type' => 'select',
          '#options' => $options,
          '#title' => $this->t('Cutter template'),
          '#empty_value' => '',
          '#weight' => -100,
        ];
      }

      if (in_array('merge', $this->getSetting('available_tools'))) {
        $element['merge'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Merge into the first file'),
          '#default_value' => $this->getSetting('merge_by_default'),
          '#weight' => -99,
        ];
      }
    }
    // Preserve selected options and display it to the user.
    else {
      if (in_array('cutter', $this->getSetting('available_tools'))) {
        if (isset($element['#default_value']['cutter_template'])) {
          $pdfCutterTemplate = $this->entityTypeManager
            ->getStorage('pdf_cutter_template')
            ->load($element['#default_value']['cutter_template']);
          if ($pdfCutterTemplate) {
            $element['cutter_template'] = [
              '#type' => 'value',
              '#value' => $pdfCutterTemplate->id(),
            ];
            $element['cutter_template_info'] = [
              '#markup' => '<div>' . $this->t('@template template will be applied.', ['@template' => $pdfCutterTemplate->label()]) . '</div>',
            ];
          }
        }
      }
      if (in_array('merge', $this->getSetting('available_tools'))) {
        if (isset($element['#default_value']['merge']) && $element['#default_value']['merge']) {
          $element['merge'] = [
            '#type' => 'value',
            '#value' => $element['#default_value']['merge'],
          ];
          $element['merge_info'] = [
            '#markup' => '<div>' . $this->t('File will be merged into the first file.') . '</div>',
          ];
        }
      }
    }

    // Add process callback
    $add = true;
    foreach ($form['#process'] as $processFunction) {
      if (is_array($processFunction) && $processFunction[0] == get_class($this)) {
        $add = false;
      }
    }
    if ($add) {
      $form['#process'][] = [get_class($this), 'formProcess'];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);

    // Store names of fields that uses the widget and their '#parents'.
    $fieldNames = $form_state->get('pdf_editor_field_names');
    if (!is_array($fieldNames) || !isset($fieldNames[$element['#field_name']])) {
      $fieldNames = is_array($fieldNames) ? $fieldNames : [];
      $fieldNames[$element['#field_name']] = array_slice($element['#parents'], 0, -1);
      $form_state->set('pdf_editor_field_names', $fieldNames);
    }

    return $element;
  }

  /**
   * Custom form process callback which add submit callback to the form main submit.
   */
  public static function formProcess($element, FormStateInterface $form_state, $form) {
    // Add form submit callback to run first. The callback updates values of the form.
    $add = true;
    foreach ($element['actions']['submit']['#submit'] as $submitFunction) {
      if (is_array($submitFunction) && $submitFunction[0] == static::class) {
        $add = false;
      }
    }
    if ($add) {
      array_unshift($element['actions']['submit']['#submit'], [static::class, 'formSubmit']);
    }

    return $element;
  }

  /**
   * Form submit callback which edits pdf files and updates values in $form_state
   * if files were merged.
   */
  public static function formSubmit($form, FormStateInterface $form_state) {
    $fields = $form_state->get('pdf_editor_field_names');
    if (is_array($fields)) {
      foreach ($fields as $fieldName => $parents) {
        $new_values = [];
        $values = NestedArray::getValue($form_state->getValues(), $parents);
        $firstFile = static::getFirstFile($values);

        foreach ($values as $delta => $value) {
          if (is_array($value['fids'])) {
            $pdfCutterTemplate = isset($value['cutter_template']) ? PdfCutterTemplate::load($value['cutter_template']) : null;
            $files = \Drupal::entityTypeManager()
              ->getStorage('file')
              ->loadMultiple($value['fids']);

            foreach ($value['fids'] as $fid) {
              $isPdf = $files[$fid]->getMimeType() == 'application/pdf';
              if ($pdfCutterTemplate && $isPdf) {
                \Drupal::service('master.pdf_tool')
                  ->overlayCanvas($files[$fid], $pdfCutterTemplate->getCanvases());
              }
              if (isset($value['merge']) && $value['merge'] && $fid != $firstFile->id() && $isPdf) {
                \Drupal::service('master.pdf_tool')
                  ->join($firstFile, $files[$fid]);
              }
              else {
                $new_value = $value;
                $new_value['fids'] = [$fid];
                $new_values[] = $new_value;
              }
            }
          }
        }
        $new_values[] = end($values);
        $form_state->setValueForElement(['#parents' => $parents], $new_values);
      }
    }
  }

  /**
   * Searches for the first pdf file in a field values.
   *
   * @param $values
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  private static function getFirstFile($values) {
    $firstFile = null;
    foreach ($values as $value) {
      if (is_array($value['fids'])) {
        $files = \Drupal::entityTypeManager()
          ->getStorage('file')
          ->loadMultiple($value['fids']);
        foreach ($files as $file) {
          if ($file->getMimeType() == 'application/pdf') {
            $firstFile = $file;
            break 2;
          }
        }
      }
    }

    return $firstFile;
  }

}
