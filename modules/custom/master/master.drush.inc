<?php

use Drupal\Core\Cache\Cache;
use Drupal\master\Master;

/**
 * Implements hook_drush_command().
 */
function master_drush_command() {
  $items = array();
  $items['generate_entity_translatable_content'] = [
    'description' => 'Generate file with all translatable fields from translatable entities',
    'drupal dependencies' => ['master'],
    'aliases' => ['master-gt'],
    'options' => [
      'language' => 'The language you would like to translate to.'
    ],
  ];

  $items['maxmind_update_db'] = [
    'description' => 'Update MaxMind Db',
    'drupal dependencies' => ['master'],
    'aliases' => ['master-mud'],
  ];

  $items['devify'] = [
    'description' => 'Sanitize database, removes personal data, removes production relation data, removes sensitive configs.',
    'drupal dependencies' => ['master'],
  ];

  return $items;
}

/**
 * Callback function drush_master_maxmind_update_db()
 */
function drush_master_maxmind_update_db(){
  \Drupal::service('master.maxmind')->dbUpdate();
}

/**
 * Call back function drush_store_update_currency_rates()
 */
function drush_master_generate_entity_translatable_content() {
  $language = drush_get_option('language', 'es');
  $contentTranslationManager = \Drupal::service('content_translation.manager');
  $entityTypeManager = \Drupal::service('entity_type.manager');
  $entityFieldManager = \Drupal::service('entity_field.manager');
  $bundleInfoService = \Drupal::service('entity_type.bundle.info');
  $entityDefinitions = $entityTypeManager->getDefinitions();
  $folderPath = 'public://translations_export/';
  global $base_url;

  /** @var \Drupal\Core\Entity\EntityTypeInterface $entityDefinition */
  foreach ($entityDefinitions as $entityDefinition) {
    $entityTypeId = $entityDefinition->id();
    if ($contentTranslationManager->isEnabled($entityTypeId)) {
      $translatableEntityDefinitions[$entityTypeId] = $entityDefinition;
      /** @var \Drupal\Core\Entity\Query\QueryFactory $query */
      foreach ($bundleInfoService->getBundleInfo($entityTypeId) as $bundle => $bundle_info) {
        $query = \Drupal::service('entity.query')->get($entityTypeId);
        if ($entityDefinition->getBundleEntityType()) {
          $query->condition('type', $bundle);
        }
        $fields = $entityFieldManager->getFieldDefinitions($entityTypeId, $bundle);
        $fieldStorageDefinitions = $entityFieldManager->getFieldStorageDefinitions($entityTypeId);
        $entity_ids = $query->execute();
        $entities = $entityTypeManager->getStorage($entityTypeId)->loadMultiple($entity_ids);
        if ($fields && $entities) {
          if (file_prepare_directory($folderPath, FILE_CREATE_DIRECTORY)) {
            $handle = fopen($folderPath . $entityTypeId . '_' . $bundle . '.rn', 'w');
            foreach ($entities as $entity) {
              $editTranslationLink = $entityDefinition->getLinkTemplates();
              $editTranslationLink = $editTranslationLink['drupal:content-translation-add'];
              $editTranslationLink = str_replace('{' . $entityTypeId . '}', $entity->id(), $editTranslationLink);
              $editTranslationLink = str_replace('{source}', 'en', $editTranslationLink);
              $editTranslationLink = str_replace('{target}', $language, $editTranslationLink);
              $record = $base_url . '/' . $language . $editTranslationLink . PHP_EOL;
              $recordFlag = FALSE;
              foreach ($fields as $fieldName => $fieldDefinition) {
                if ($fieldDefinition->isTranslatable() &&
                  !empty($fieldStorageDefinitions[$fieldName]) &&
                  $fieldStorageDefinitions[$fieldName]->getProvider() != 'content_translation' &&
                  !in_array($fieldName, [
                    $entityDefinition->getKey('langcode'),
                    $entityDefinition->getKey('default_langcode'),
                    'revision_translation_affected'
                  ])
                  && !($entityTypeId == 'base_product' && $bundle == 'ticket_product' && $fieldName == 'name')
                  && !($fieldName == 'description' && !$entity->get($fieldName)
                      ->getValue()[0]['value'])
                ) {
                  $record .= '  ' . $fieldName . PHP_EOL;
                  foreach ($entity->get($fieldName)->getValue() as $value) {
                    foreach ($value as $field_sub_key => $item) {
                      if ($item) {
                        if (($entityTypeId == 'base_product' && $bundle == 'ticket_product' && !$entity->getDescription())
                          || $field_sub_key == 'format'
                          || $fieldName == 'status'
                          || $field_sub_key == 'country_code'
                        ) {
                        }
                        else {
                          $record .= '    ' . $field_sub_key . ' : ' . $item . PHP_EOL;
                          $record .= '    translation : ' . PHP_EOL;
                          $op[$entityTypeId][$bundle][$entity->id()][$fieldName][$field_sub_key] = $item;
                          $recordFlag = TRUE;
                        }
                      }
                    }
                  }
                }
              }
              if ($recordFlag) {
                fwrite($handle, $record);
              }
            }
            fclose($handle);
          }
        }
      }
    }
  }

  drush_print('Files have been generated.');
}

function drush_master_devify() {
  \Drupal::moduleHandler()->invokeAll('drush_master_devify');

  \Drupal::service('module_installer')->install(['devel', 'stage_file_proxy']);
  $config = \Drupal::configFactory()->getEditable('stage_file_proxy.settings');
  $origin = $originDir = '';
  switch (Master::siteCode()) {
    case Master::SITE_CODE_RAIL_NINJA:
      $origin = 'https://rail.ninja';
      $originDir = 'sites/default/files';
      break;
    case Master::SITE_CODE_RUSSIAN_TRAINS:
      $origin = 'https://www.russiantrains.com';
      $originDir = 'sites/russiantrains.com/files';
      break;
    case Master::SITE_CODE_RUSSIAN_TRAIN_TICKETS:
      $origin = 'https://www.russiantraintickets.com';
      $originDir = 'sites/russiantraintickets.com/files';
      break;
  }
  $config->set('origin', $origin);
  $config->set('origin_dir', $originDir);
  $config->set('verify', false);
  $config->save();
  drush_print('Installed development modules');

  foreach (Cache::getBins() as $bin) {
    $bin->deleteAll();
  }
}

/**
 * Truncates all tables where the entity data is stored.
 *
 * @param $entityTypeId
 */
function master_clear_entity_table($entityTypeId) {
  foreach (\Drupal::entityTypeManager()->getStorage($entityTypeId)->getTableMapping()->getTableNames() as $tableName) {
    \Drupal::database()->truncate($tableName)->execute();
  }
}
