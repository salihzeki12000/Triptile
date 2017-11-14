<?php

namespace Drupal\rn_field\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Pdf cutter template entities.
 */
interface PdfCutterTemplateInterface extends ConfigEntityInterface {

  /**
   * Gets list of canvases parameters.
   *
   * @return array
   */
  public function getCanvases();

  /**
   * Sets new list of canvases parameters.
   *
   * @param array $canvases
   * @return static
   */
  public function setCanvases(array $canvases);

  /**
   * Appends canvas parameters to the end of existing list.
   *
   * @param array $canvas
   * @return static
   */
  public function appendCanvas(array $canvas);

}
