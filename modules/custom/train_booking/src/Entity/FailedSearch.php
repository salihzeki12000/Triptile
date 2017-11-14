<?php

namespace Drupal\train_booking\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\master\Entity\CountableStatEntityTrait;

/**
 * Defines the Failed search entity.
 *
 * @ingroup train_booking
 *
 * @ContentEntityType(
 *   id = "failed_search",
 *   label = @Translation("Failed search"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\train_booking\FailedSearchListBuilder",
 *     "views_data" = "Drupal\train_booking\Entity\FailedSearchViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\train_booking\Form\FailedSearchForm",
 *       "add" = "Drupal\train_booking\Form\FailedSearchForm",
 *       "edit" = "Drupal\train_booking\Form\FailedSearchForm",
 *       "delete" = "Drupal\train_booking\Form\FailedSearchDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "failed_search",
 *   admin_permission = "administer failed search entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/statistic/failed-search/{failed_search}",
 *     "add-form" = "/admin/trains/statistic/failed-search/add",
 *     "edit-form" = "/admin/trains/statistic/failed-search/{failed_search}/edit",
 *     "delete-form" = "/admin/trains/statistic/failed-search/{failed_search}/delete",
 *     "collection" = "/admin/trains/statistic/failed-search",
 *   },
 *   field_ui_base_route = "entity.failed_search.settings",
 *   settings_form = "Drupal\train_booking\Form\FailedSearchSettingsForm"
 * )
 *
 * // @todo Add cron to delete old entities when table gets huge.
 */
class FailedSearch extends ContentEntityBase implements FailedSearchInterface {

  use EntityChangedTrait;
  use CountableStatEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function getDepartureStation() {
    return $this->get('departure_station')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setDepartureStation($departure_station) {
    $this->set('departure_station', $departure_station);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getArrivalStation() {
    return $this->get('arrival_station')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setArrivalStation($arrival_station) {
    $this->set('arrival_station', $arrival_station);
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

    $fields['departure_station'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Departure station'))
      ->setSetting('target_type', 'station')
      ->setSetting('handler', 'only_enabled')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['arrival_station'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Arrival station'))
      ->setSetting('target_type', 'station')
      ->setSetting('handler', 'only_enabled')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['departure_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Departure date'))
      ->setDescription(t('The date, which user has searched.'))
      ->setSettings(array(
        'datetime_type' => 'date',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -4,
        'settings' => [
          'format_type' => 'html_date',
        ]
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_default',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['order_depth'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Order depth'))
      ->setDescription(t('The order depth of the request.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Count'))
      ->setDescription(t('The count of the Failed searches.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 3,
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
