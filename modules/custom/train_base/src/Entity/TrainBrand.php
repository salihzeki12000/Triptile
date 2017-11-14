<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Train brand entity.
 *
 * @ingroup train_base
 *
 * @ContentEntityType(
 *   id = "train_brand",
 *   label = @Translation("Train brand"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\train_base\TrainBrandListBuilder",
 *     "views_data" = "Drupal\train_base\Entity\TrainBrandViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\train_base\Form\TrainBrandForm",
 *       "add" = "Drupal\train_base\Form\TrainBrandForm",
 *       "edit" = "Drupal\train_base\Form\TrainBrandForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "train_brand",
 *   data_table = "train_brand_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer train entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/train-brand/{train_brand}",
 *     "add-form" = "/admin/trains/train-brand/add",
 *     "edit-form" = "/admin/trains/train-brand/{train_brand}/edit",
 *     "delete-form" = "/admin/trains/train-brand/{train_brand}/delete",
 *     "collection" = "/admin/trains/train-brand",
 *   },
 *   field_ui_base_route = "entity.train_brand.settings",
 *   settings_form = "Drupal\train_base\Form\TrainBrandSettingsForm"
 * )
 */
class TrainBrand extends ContentEntityBase implements TrainBrandInterface {
  
  use EntityChangedTrait;
  
  /**
   * Возвращает название бренда поезда
   *
   * @return string | null
   */
  public function getName() {
    return $this->get('name')->value;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }
  
  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp) {
    $this->set('created', $timestamp);
    
    return $this;
  }
  
  /**
   * Возвращает миссив сущностей расписаний поездов этого бренда
   *
   * @return \Drupal\local_train_provider\Entity\TimetableEntry[]
   */
  public function getTimetableEntries(): array {
    return \Drupal::entityTypeManager()
      ->getStorage('timetable_entry')
      ->loadMultiple(array_map(function (Train $train) {
        return $train->id();
      }, $this->getTrains()));
  }
  
  /**
   * Возвращает миссив сущностей классов вогонов этого бренда
   *
   * @return \Drupal\train_base\Entity\CoachClass[]
   */
  public function getCoachClasses(): array {
    return \Drupal::entityTypeManager()
      ->getStorage('coach_class')
      ->loadByProperties(['train_brand' => $this->id()]);
  }
  
  /**
   * Возвращает миссив сущностей поездов
   *
   * @return \Drupal\train_base\Entity\Train[]
   */
  public function getTrains(): array {
    return \Drupal::entityTypeManager()
      ->getStorage('train')
      ->loadByProperties(['train_brand' => $this->id()]);
  }
  
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entityType): array {
    return array_merge(parent::baseFieldDefinitions($entityType), [
      'name' => BaseFieldDefinition::create('string')
        ->setLabel(t('Name'))
        ->setTranslatable(true)
        ->setDescription(t('The Train name'))
        ->setSettings([
          'max_length' => 50,
          'text_processing' => 0,
        ])
        ->setDisplayOptions('view', [
          'label' => 'above',
          'type' => 'string',
          'weight' => -5,
        ])
        ->setDisplayOptions('form', [
          'type' => 'string_textfield',
          'weight' => -5,
        ])
        ->setDisplayConfigurable('form', true)
        ->setDisplayConfigurable('view', true),
      'created' => BaseFieldDefinition::create('created')
        ->setLabel(t('Created'))
        ->setDescription(t('The time that the entity was created.')),
      'changed' => BaseFieldDefinition::create('changed')
        ->setLabel(t('Changed'))
        ->setDescription(t('The time that the entity was last edited.')),
    ]);
  }
  
}
