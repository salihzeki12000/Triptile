<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\master\Entity\ContentEntity;

/**
 * Defines the Station entity.
 *
 * @ingroup train_base
 *
 * @ContentEntityType(
 *   id = "station",
 *   label = @Translation("Station"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\train_base\StationListBuilder",
 *     "views_data" = "Drupal\train_base\Entity\StationViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\train_base\Form\StationForm",
 *       "add" = "Drupal\train_base\Form\StationForm",
 *       "edit" = "Drupal\train_base\Form\StationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "station",
 *   data_table = "station_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer station entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/station/{station}",
 *     "add-form" = "/admin/trains/station/add",
 *     "edit-form" = "/admin/trains/station/{station}/edit",
 *     "delete-form" = "/admin/trains/station/{station}/delete",
 *     "collection" = "/admin/trains/station",
 *   },
 *   field_ui_base_route = "entity.station.settings",
 *   settings_form = "Drupal\train_base\Form\StationSettingsForm"
 * )
 */
class Station extends ContentEntity  implements StationInterface {

  use EntityChangedTrait;

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
  public function isEnabled() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->getTranslated('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountry() {
    return $this->get('address')->first()->getCountryCode();
  }

  /**
   * {@inheritdoc}
   */
  public function getPopularRoutes() {
    return $this->get('popular_routes')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Station entity.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setTranslatable(TRUE)
      ->setRequired(true)
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -11,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -11,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setDescription(t('The address of the Station.'))
      ->setTranslatable(true)
      ->setSettings(array(
        'available_countries' => [],
        'fields' => [
          'administrativeArea' => 0,
          'locality' => 'locality',
          'dependentLocality' => 0,
          'postalCode' => 0,
          'sortingCode' => 0,
          'addressLine1' => 'addressLine1',
          'addressLine2' => 0,
          'organization' => 0,
          'givenName' => 0,
          'additionalName' => 0,
          'familyName' => 0,
        ],
        'langcode_override' => '',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'address_default',
        'weight' => -9,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'address_default',
        'weight' => -9,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['parent_station'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent station'))
      ->setDescription(t('Has to be used to attach a rail station to main station, i.e Leningradskiy station and Moscow.'))
      ->setSetting('target_type', 'station')
      ->setSetting('handler', 'only_enabled')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => -7,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => -7,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['geolocation'] = BaseFieldDefinition::create('geolocation')
      ->setLabel(t('Geolocation'))
      ->setDescription(t('The geolocation of the Station.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'geolocation_latlng',
        'weight' => -9,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'geolocation_latlng',
        'weight' => -9,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $zones = system_time_zones();
    $fields['timezone'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Timezone'))
      ->setDescription(t('Timezone.'))
      ->setRevisionable(TRUE)
      ->setRequired(true)
      ->setSettings(array(
        'allowed_values' => $zones,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('A boolean indicating whether the Station is on/off.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['popular_routes'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Popular routes'))
      ->setDescription(t('Used to display popular routes on LP.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'station')
      ->setSetting('handler', 'only_enabled')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => -5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['supplier_mapping'] = BaseFieldDefinition::create('supplier_mapping')
      ->setLabel(t('Supplier mapping'))
      ->setDescription(t('Supplier mapping.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'supplier')
      ->setSetting('handler', 'only_enabled')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => -5,
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

  public function __toString() {
    return $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getParentStation() {
    return $this->get('parent_station')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimezone() {
    if ($this->get('timezone')->value) {
      return new \DateTimeZone($this->get('timezone')->value);
    }
    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryCode() {
    return $this->get('address')->first()->getCountryCode();
  }

  /**
   * {@inheritdoc}
   */
  public function getLocality(): string {
    return $this->getAddress()->getLocality();
  }

  /**
   * {@inheritdoc}
   */
  public function getAddress() {
    return $this->get('address')->first();
  }

  /**
   * @param \Drupal\train_base\Entity\Station $station
   * @return float
   */
  public function getDistanceTo(Station $station): float {
      $earthRadius = 6371;

      $fromLt = deg2rad($this->getLatitude());
      $fromLng = deg2rad($this->getLongitude());
      $toLt = deg2rad($station->getLatitude());
      $toLng = deg2rad($station->getLongitude());

      return $earthRadius * acos(sin($fromLt) * sin($toLt) + cos($fromLt) * cos($toLt) * cos($fromLng - $toLng));
  }

  /**
   * @return \Drupal\geolocation\Plugin\Field\FieldType\GeolocationItem
   */
  private function getGeolocation() {
    return $this->get('geolocation')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function getLatitude(): float {
    return $this->getGeolocation()->get('lat')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getLongitude(): float {
    return $this->getGeolocation()->get('lng')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getStationChildrenIds() {
    return \Drupal::entityQuery('station')
      ->condition('parent_station', $this->id())
      ->condition('status', 1)
      ->execute()
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function getStationChildren(): array {
    if (!empty($entity_ids = $this->getStationChildrenIds())) {
      return \Drupal::entityTypeManager()->getStorage('station')->loadMultiple($entity_ids);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getStationWithChildrenIds() {
    return array_merge($this->getStationChildrenIds(), [$this->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getStationCodeBySupplierCode($supplierCode) {
    $supplierId = \Drupal::entityQuery('supplier')
      ->condition('code', $supplierCode)
      ->condition('status', 1)
      ->range(0, 1)
      ->execute()
    ;

    if ($supplierId) {
      $supplierId = reset($supplierId);
      return $this->getStationCodeBySupplierId($supplierId);
    }

    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function getStationCodeBySupplierId($supplierId) {
    if ($supplierId && $this->get('supplier_mapping')->getValue()) {
      foreach ($this->get('supplier_mapping')->getValue() as $mappingObject) {
        if ($mappingObject['target_id'] == $supplierId) {
          return $mappingObject['code'];
        }
      }
    }
    return null;
  }
}
