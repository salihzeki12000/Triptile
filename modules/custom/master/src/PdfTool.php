<?php

namespace Drupal\master;

use Drupal\Core\File\FileSystem;
use Drupal\File\Entity\File;
use ZendPdf\Color\Html as ZendPdfColorHtml;
use ZendPdf\Page as ZendPdfPage;
use ZendPdf\PdfDocument as ZendPdfDocument;

class PdfTool {

  /**
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * PdfService constructor.
   *
   * @param \Drupal\Core\File\FileSystem $file_system
   */
  public function __construct(FileSystem $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * Adds a white canvas to a pdf file.
   *
   * @param \Drupal\File\Entity\File $file
   * @param array $canvases
   */
  public function overlayCanvas(File $file, array $canvases) {
    $filePath = $this->fileSystem->realpath($file->getFileUri());
    $pdfDocument = ZendPdfDocument::load($filePath);

    foreach ($canvases as $canvas) {
      if (isset($pdfDocument->pages[$canvas['page'] - 1])) {
        /**
         * @var ZendPdfPage $pdfPage
         */
        $pdfPage = $pdfDocument->pages[$canvas['page'] - 1];

        $x1 = $canvas['x'];
        $y1 = $canvas['y'];

        $x2 = $x1 + $canvas['width'];
        $y2 = $y1 + $canvas['height'];

        $color = $canvas['color'] ?? '#ffffff';

        $pdfPage->setFillColor(ZendPdfColorHtml::color($color));
        $pdfPage->drawRectangle($x1, $y1, $x2, $y2, ZendPdfPage::SHAPE_DRAW_FILL);
      }
    }

    $pdfDocument->save($filePath);
  }

  /**
   * Joins several pdf files into main pdf file.
   *
   * @param \Drupal\File\Entity\File $targetFile
   * @param \Drupal\File\Entity\File $addedFile
   */
  public function join(File $targetFile, File $addedFile) {
    $finalFilePath = $this->fileSystem->realpath($targetFile->getFileUri());
    $finalPdfDocument = ZendPdfDocument::load($finalFilePath);

    $filePath = $this->fileSystem->realpath($addedFile->getFileUri());
    $pdfDocument = ZendPdfDocument::load($filePath);

    foreach ($pdfDocument->pages as $k => $page) {
      $finalPdfDocument->pages[] = clone $page;
    }

    $finalPdfDocument->save($finalFilePath);
  }

}
