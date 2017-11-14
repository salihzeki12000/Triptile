<?php

namespace Drupal\train_base\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\train_base\ComputedField\TrainAverageRatingComputed;

/**
 * Defines the Train entity.
 *
 * @ingroup train_base
 *
 * @ContentEntityType(
 *   id = "train",
 *   label = @Translation("Train"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\train_base\TrainListBuilder",
 *     "views_data" = "Drupal\train_base\Entity\TrainViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\train_base\Form\TrainForm",
 *       "add" = "Drupal\train_base\Form\TrainForm",
 *       "edit" = "Drupal\train_base\Form\TrainForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\master\Entity\EntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "train",
 *   data_table = "train_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer train entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "number",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/trains/train/{train}",
 *     "add-form" = "/admin/trains/train/add",
 *     "edit-form" = "/admin/trains/train/{train}/edit",
 *     "delete-form" = "/admin/trains/train/{train}/delete",
 *     "collection" = "/admin/trains/train",
 *   },
 *   field_ui_base_route = "entity.train.settings",
 *   settings_form = "Drupal\train_base\Form\TrainSettingsForm"
 * )
 */
class Train extends ContentEntityBase implements TrainInterface {

  use EntityChangedTrait;

  const RATING_EXCELLENT_MIN = 9;
  const RATING_GOOD_MIN = 8;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumber() {
    return $this->get('number')->value;
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
  public function getSupplier() {
    return $this->get('supplier')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrainBrand() {
    return $this->get('train_brand')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrainClass() {
    return $this->get('train_class')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTPRating() {
    return (float) $this->get('tp_rating')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getInternalRating() {
    return (float) $this->get('internal_rating')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountOfReviews() {
    return $this->get('count_of_reviews')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isBoardingPassRequired() {
    return $this->get('boarding_pass_required')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isEticketAvailable() {
    return $this->get('eticket_available')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBoardingPassRequired(bool $boarding_pass_required) {
    $this->set('boarding_pass_required', $boarding_pass_required);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Number'))
      ->setDescription(t('The Train number'))
      ->setRequired(true)
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
      ->setDisplayConfigurable('view', true);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The Train name'))
      ->setTranslatable(true)
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
      ->setDisplayConfigurable('view', true);

    $fields['tp_rating'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Trustpilot rating'))
      ->setDescription(t('Average rating from Trustpilot.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['internal_rating'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Internal rating'))
      ->setDescription(t('Our internal train rating.'))
      ->setRequired(true)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['count_of_reviews'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Count of reviews'))
      ->setDescription(t('Count of reviews posted on Trustpilot.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['content_page'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Content page'))
      ->setDescription(t('Reference to a Train basic page: Each train can have own page with detailed description.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', ['target_bundles' => ['page']])
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['supplier'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Supplier'))
      ->setDescription(t('Reference to a Supplier.'))
      ->setSetting('target_type', 'supplier')
      ->setSetting('handler', 'only_enabled')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['train_brand'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Brand'))
      ->setDescription(t('Reference to a Train brand.'))
      ->setSetting('target_type', 'train_brand')
      ->setSetting('handler', 'default')
      ->setRequired(false)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['train_class'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Class'))
      ->setDescription(t('Reference to a Train class.'))
      ->setSetting('target_type', 'train_class')
      ->setSetting('handler', 'with_supplier')
      ->setRequired(true)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['boarding_pass_required'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Boarding pass required'))
      ->setDescription(t('Boarding password required.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['eticket_available'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Eticket available'))
      ->setDescription(t('Eticket available.'))
      ->setDefaultValue(true)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['average_rating'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Average rating'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => -5,
      ])
      ->setComputed(true)
      ->setClass(TrainAverageRatingComputed::class);

    $fields['message'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Message'))
      ->setTranslatable(true)
      ->setDescription(t('A message that will be displayed on timetable to clients.'))
      ->setCardinality(1)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getAverageRating() {
    return $this->get('average_rating')->getValue();
  }

  /**
   * @param float $rating
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup | string
   */
  public static function getRatingPhrase(float $rating) {
    if ($rating >= static::RATING_EXCELLENT_MIN) {
      $output = t('Excellent');
    } elseif ($rating >= static::RATING_GOOD_MIN &&
      $rating < static::RATING_EXCELLENT_MIN
    ) {
      $output = t('Good');
    } else {
      $output = '';
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->get('message')->processed;
  }

}
