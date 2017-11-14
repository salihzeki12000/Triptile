<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Coach scheme entity.
 *
 * @ingroup train_base
 *
 * @ContentEntityType(
 *   id = "coach_scheme",
 *   label = @Translation("Coach scheme"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\train_base\CoachSchemeListBuilder",
 *     "views_data" = "Drupal\train_base\Entity\CoachSchemeViewsData",
 *     "form" = {
 *       "default" = "Drupal\train_base\Form\CoachSchemeForm",
 *       "add" = "Drupal\train_base\Form\CoachSchemeForm",
 *       "edit" = "Drupal\train_base\Form\CoachSchemeForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "coach_scheme",
 *   data_table = "coach_scheme_field_data",
 *   admin_permission = "administer coach scheme entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/coach-scheme/{coach_scheme}",
 *     "add-form" = "/admin/trains/coach-scheme/add",
 *     "edit-form" = "/admin/trains/coach-scheme/{coach_scheme}/edit",
 *     "delete-form" = "/admin/trains/coach-scheme/{coach_scheme}/delete",
 *     "collection" = "/admin/trains/coach-scheme",
 *   },
 *   field_ui_base_route = "entity.coach_scheme.settings",
 *   settings_form = "Drupal\train_base\Form\CoachSchemeSettingsForm"
 * )
 */
class CoachScheme extends ContentEntityBase implements CoachSchemeInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Coach scheme entity.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // @todo: Create custom field "Scheme: special complex field with special editor".

    $fields['coach_class'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Coach class'))
      ->setDescription(t('Reference to a Coach class.'))
      ->setSetting('target_type', 'coach_class')
      ->setSetting('handler', 'with_supplier')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => -2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => -2,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
