<?php

namespace Drupal\rn_field\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the pdf cutter template entity.
 *
 * @ConfigEntityType(
 *   id = "pdf_cutter_template",
 *   label = @Translation("Pdf cutter template"),
 *   handlers = {
 *     "list_builder" = "Drupal\rn_field\PdfCutterTemplateListBuilder",
 *     "form" = {
 *       "add" = "Drupal\rn_field\Form\PdfCutterTemplate\PdfCutterTemplateForm",
 *       "edit" = "Drupal\rn_field\Form\PdfCutterTemplate\PdfCutterTemplateForm",
 *       "delete" = "Drupal\rn_field\Form\PdfCutterTemplate\PdfCutterTemplateDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "pdf_cutter_template",
 *   admin_permission = "administer pdf cutter templates",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/rail-ninja-config/pdf-cutter-template/{pdf_cutter_template}",
 *     "add-form" = "/admin/config/rail-ninja-config/pdf-cutter-template/add",
 *     "edit-form" = "/admin/config/rail-ninja-config/pdf-cutter-template/{pdf_cutter_template}/edit",
 *     "delete-form" = "/admin/config/rail-ninja-config/pdf-cutter-template/{pdf_cutter_template}/delete",
 *     "collection" = "/admin/config/rail-ninja-config/pdf-cutter-template"
 *   }
 * )
 */
class PdfCutterTemplate extends ConfigEntityBase implements PdfCutterTemplateInterface {

  /**
   * The Pdf cutter template ID
   *
   * @var string
   */
  protected $id;

  /**
   * The Pdf cutter template label
   *
   * @var string
   */
  protected $label;
  
  /**
   * List of canvases parameters.
   *
   * @var array
   */
  protected $canvases = [];


  /**
   * {@inheritdoc}
   */
  public function getCanvases() {
    return $this->canvases;
  }

  /**
   * {@inheritdoc}
   */
  public function setCanvases(array $canvases) {
    // TODO Need to validate canvases
    $this->canvases = $canvases;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function appendCanvas(array $canvas) {
    // TODO Need to validate canvas
    $this->canvases[] = $canvas;
    return $this;
  }

}
