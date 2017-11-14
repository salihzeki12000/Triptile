<?php

namespace Drupal\master;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal;


/**
 * Class InfoHolderBase
 * @package Drupal\master
 */
abstract class InfoHolderBase {

  /**
   * Contains class property names that are arrays of entities.
   *
   * @var array
   */
  protected static $arraysOfEntities = [];

  /**
   * @var array
   */
  protected $_entityIds = [];

  /**
   * @var array
   */
  protected $_entityArrays = [];

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $this->_entityIds = [];
    $this->_entityArrays = [];
    $vars = get_object_vars($this);
    foreach ($vars as $key => $value) {
      if ($value instanceof ContentEntityInterface) {
        // If a class member is an entity object, only store its ID so it can be
        // used to get a fresh object on unserialization.
        $this->_entityIds[$value->getEntityTypeId()][$key] = $value->id();
        unset($vars[$key]);
      }
    }
    foreach ($this->arraysOfEntities() as $key) {
      $this->_entityArrays[$key] = [];
      foreach ($this->$key as $entity_key => $value) {
        if ($value instanceof ContentEntityInterface) {
          $this->_entityArrays[$key][$value->getEntityTypeId()][$entity_key] = $value->id();
          unset($vars[$key]);
        }
      }
    }

    return array_keys($vars);
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    foreach ($this->_entityIds as $entityTypeId => $ids) {
      $entities = Drupal::entityTypeManager()->getStorage($entityTypeId)->loadMultiple($ids);
      foreach ($ids as $key => $id) {
        if ($key) {
          $this->$key = $entities[$id];
        }
      }
    }

    foreach ($this->_entityArrays as $key => $items) {
      $this->$key = [];
      foreach ($items as $entityTypeId => $ids) {
        $entities = Drupal::entityTypeManager()->getStorage($entityTypeId)->loadMultiple($ids);
        foreach ($ids as $entity_key => $id) {
          $this->$key[$entity_key] = $entities[$id];
        }
      }
    }

    $this->_entityIds = [];
    $this->_entityArrays = [];
  }

  /**
   * Gets class property names that are arrays of entities.
   *
   * @return mixed
   */
  protected function arraysOfEntities() {
    return static::$arraysOfEntities;
  }

}
