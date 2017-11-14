<?php

namespace Drupal\russian_trains_migration;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;

class NodeUpdater {

  /**
   * @var \Drupal\node\Entity\Node $entity
   */
  protected $node;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\file\FileUsage\DatabaseFileUsageBackend $fileUsage
   */
  protected $fileUsage;

  /**
   * @var bool
   */
  protected $needToSave;

  public function __construct(EntityTypeManager $entity_type_manager, DatabaseFileUsageBackend $file_usage) {
    $this->fileUsage = $file_usage;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Update images
   */
  public function updateImages() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $nodeStorage */
    $nodeStorage =  $this->entityTypeManager->getStorage('node');
    $query = $nodeStorage->getQuery();
    $query->condition('type', ['page', 'blog', 'train_page'], 'IN');
    $nodesIds = $query->execute();
    if ($nodesIds) {
      $nodes = $nodeStorage->loadMultiple($nodesIds);
      /** @var \Drupal\node\Entity\Node $node */
      foreach ($nodes as $node) {
        $this->needToSave = false;
        $this->node = $node;
        $languages = $node->getTranslationLanguages();
        /** @var \Drupal\Core\Language\LanguageInterface $language */
        foreach ($languages as $language) {
          $currentLanguageNode = $node->getTranslation($language->getId());
          $body = $currentLanguageNode->get('body')->value;
          $doc = \phpQuery::newDocument($body);
          $images = pq('img');
          if ($images->elements) {
            $images->each([$this, 'imageHandler']);
            $updatedBody = $doc->html();
            $currentLanguageNode->body->setValue(['value' => $updatedBody, 'summary' => $node->get('body')->summary, 'format' => $node->get('body')->format]);
          }
        }
        if ($this->needToSave) {
          $node->save();
        }
      }
    }
  }

  /**
   * @param \DOMElement $element
   */
  public function imageHandler($element) {
    $src = $element->getAttribute('src');
    if (!strpos($src, 'sites/russiantrains.com/files/inline-images/migrated')) {
      $parsedUrl = parse_url($src);
      if (!$parsedUrl['host'] || $parsedUrl['host'] == 'www.russiantrains.com' || $parsedUrl['host'] == 'russiantrains.com') {
        if (substr($parsedUrl['path'], 0, 9) == '../../../') {
          $parsedUrl['path'] = substr($parsedUrl['path'], 9);
        }
        $parsedUrl['path'] = substr($parsedUrl['path'], 0, 1) != '/' ? '/' . $parsedUrl['path'] : $parsedUrl['path'];
        $imageName = str_replace('/', '_', substr($parsedUrl['path'], 1));
        $imageName = urldecode($imageName);
        $url = 'http://www.russiantrains.com' . $parsedUrl['path'];
        $headerResponse = @get_headers($url, 1);
        if (strpos($headerResponse[0], '404') === false) {
          $data = file_get_contents($url);
          if ($data) {
            $directoryPath = 'public://inline-images/migrated';
            file_prepare_directory($directoryPath, FILE_CREATE_DIRECTORY);
            $destination = $directoryPath . '/' . $imageName;
            $file = file_save_data($data, $destination, FILE_EXISTS_REPLACE);
            if ($file) {
              $this->needToSave = true;
              $this->fileUsage->add($file, 'editor', 'node', $this->node->id());
              $newSrc = '/sites/russiantrains.com/files/inline-images/migrated/' . $imageName;
              $element->setAttribute('src', $newSrc);
            }
          }
        }
      }
    }
  }
}
