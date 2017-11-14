<?php

namespace Drupal\local_train_provider\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'schedule_default' widget.
 *
 * @FieldWidget(
 *   id = "schedule_default",
 *   label = @Translation("Schedule Widget"),
 *   field_types = {
 *     "schedule"
 *   }
 * )
 */
class ScheduleDefault extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $date_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->dateStorage = $date_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $weekdays_options = [
      1 => 'Monday',
      2 => 'Tuesday',
      4 => 'Wednesday',
      8 => 'Thursday',
      16 => 'Friday',
      32 => 'Saturday',
      64 => 'Sunday',
    ];
    $default_values = [];
    foreach ($weekdays_options as $key => $value) {
      if (($items[$delta]->weekdays & $key) == $key) {
        $default_values[] = $key;
      }
    }
    $element['weekdays'] = [
      '#type' => 'select',
      '#title' => t('Weekdays'),
      '#options' => $weekdays_options,
      '#multiple' => TRUE,
      '#default_value' => isset($items[$delta]->weekdays) ? $default_values : NULL,
      '#placeholder' => $this->getSetting('placeholder'),
      '#states' => [
        'disabled' => [
          [
            'input[name="schedule[0][every_n_days]"]' => ['filled' => TRUE],
          ],
          [
            'select[name="schedule[0][even_days]"]' => [
              ['value' => 1],
              ['value' => 2],
            ],
          ],
        ],
      ],
    ];

    /*$element['weekdays'] = [
      '#type' => 'number',
      '#title' => t('weekdays'),
      '#default_value' => isset($items[$delta]->weekdays) ? $items[$delta]->weekdays : NULL,
    ];*/

    $element['even_days'] = [
      '#type' => 'select',
      '#title' => t('Even/Odd days'),
      '#empty_value' => '',
      '#options' => [1 => 'Odd days', 2 => 'Even days'],
      '#default_value' => isset($items[$delta]->even_days) ? $items[$delta]->even_days : NULL,
      '#placeholder' => $this->getSetting('placeholder'),
      '#states' => [
        'disabled' => [
          [
            'input[name="schedule[0][every_n_days]"]' => ['filled' => TRUE],
          ],
          /*[
            'select[name="schedule[0][weekdays][]"]' => [
              ['value' => 1],
              ['value' => 2],
              ['value' => 4],
              ['value' => 8],
              ['value' => 16],
              ['value' => 32],
              ['value' => 64],
            ],
          ],
          [
            'select[name="schedule[0][weekdays][]"]' => [['value' => 1],],
          ],*/
        ]
      ],
    ];

    $element['every_n_days'] = array(
      '#type' => 'number',
      '#title' => $this->t('Every N days'),
      '#default_value' => isset($items[$delta]->every_n_days) ? $items[$delta]->every_n_days : NULL,
      '#states' => [
        'disabled' => [
          [
            'select[name="schedule[0][even_days]"]' => [
              ['value' => 1],
              ['value' => 2],
            ],
          ],
          /*[
            'select[name="schedule[0][weekdays][]"]' => [['value' => 1],],
          ],*/
        ],
      ],
    );

    // Identify the type of date and time elements to use.
    switch ($this->getFieldSetting('datetime_type')) {
      case DateTimeItem::DATETIME_TYPE_DATE:
        $date_type = 'date';
        $time_type = 'none';
        $date_format = $this->dateStorage->load('html_date')->getPattern();
        $time_format = '';
        break;

      default:
        $date_type = 'date';
        $time_type = 'time';
        $date_format = $this->dateStorage->load('html_date')->getPattern();
        $time_format = $this->dateStorage->load('html_time')->getPattern();
        break;
    }

    // @todo Does make it required if ever_n_days has been filled?
    $element['available_from'] = $element['available_until'] = array(
      '#type' => 'datetime',
      '#default_value' => NULL,
      '#date_increment' => 1,
      '#date_timezone' => drupal_get_user_timezone(),
      '#date_date_format' => $date_format,
      '#date_date_element' => $date_type,
      '#date_date_callbacks' => array(),
      '#date_time_format' => $time_format,
      '#date_time_element' => $time_type,
      '#date_time_callbacks' => array(),
    );
    $element['available_from']['#title'] = 'Available from';
    $element['available_from']['#weight'] = 5;
    $element['available_until']['#title'] = 'Available until';
    $element['available_until']['#weight'] = 6;

    if ($items[$delta]->available_from_date) {
      $date = $items[$delta]->available_from_date;
      // The date was created and verified during field_load(), so it is safe to
      // use without further inspection.
      if ($this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE) {
        // A date without time will pick up the current time, use the default
        // time.
        datetime_date_default_time($date);
      }
      $date->setTimezone(new \DateTimeZone($element['available_from']['#date_timezone']));
      $element['available_from']['#default_value'] = $date;
    }

    if ($items[$delta]->available_until_date) {
      $date = $items[$delta]->available_until_date;
      // The date was created and verified during field_load(), so it is safe to
      // use without further inspection.
      if ($this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE) {
        // A date without time will pick up the current time, use the default
        // time.
        datetime_date_default_time($date);
      }
      $date->setTimezone(new \DateTimeZone($element['available_until']['#date_timezone']));
      $element['available_until']['#default_value'] = $date;
    }

    $element['#element_validate'] = [
      [$this, 'validate'],
    ];

    return $element;
  }

  /**
   * Custom field validation callback.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validate($element, FormStateInterface $form_state) {
    // If field is required one of fields 'weekdays', 'even_days' or 'every_n_days' must be filled out.
    if ($element['#required']) {
      $empty = empty($element['even_days']['#value']) && empty($element['every_n_days']['#value']);
      if ($empty) {
        foreach ($element['weekdays']['#value'] as $value) {
          $empty = false;
        }
      }
      if ($empty) {
        $form_state->setError($element['weekdays'], $this->t('One of fields %weekdays, %even_days or %every_n_days must be filled out.', [
          '%weekdays' => $element['weekdays']['#title'],
          '%even_days' => $element['even_days']['#title'],
          '%every_n_days' => $element['every_n_days']['#title'],
        ]));
        $form_state->setError($element['even_days']);
        $form_state->setError($element['every_n_days']);
      }
    };

    // If 'every_n_days' is filled out, 'available_from' must be provided.
    if (!empty($element['every_n_days']['#value']) && empty($element['available_from']['#value']['date'])) {
      $form_state->setError($element['available_from'], $this->t('If %every_n_days field is filled out than %available_from must be provided.', [
        '%every_n_days' => $element['every_n_days']['#title'],
        '%available_from' => $element['available_from']['#title'],
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      if (empty($item['weekdays'])) {
        $item['weekdays'] = NULL;
      }
      else {
        $result = 0;
        foreach ($item['weekdays'] as $value) {
          $result = $result|$value;
        }
        $item['weekdays'] = $result;
      }
      if (empty($item['every_n_days'])) {
        $item['every_n_days'] = NULL;
      }
      else {
        $item['even_days'] = NULL;
      }
      if (empty($item['even_days'])) {
        $item['even_days'] = NULL;
      }

      // The widget form element type has transformed the value to a
      // DrupalDateTime object at this point. We need to convert it back to the
      // storage timezone and format.
      if (!empty($item['available_from']) && $item['available_from'] instanceof DrupalDateTime) {
        $date = $item['available_from'];
        switch ($this->getFieldSetting('datetime_type')) {
          case DateTimeItem::DATETIME_TYPE_DATE:
            // If this is a date-only field, set it to the default time so the
            // timezone conversion can be reversed.
            datetime_date_default_time($date);
            $format = DATETIME_DATE_STORAGE_FORMAT;
            break;

          default:
            $format = DATETIME_DATETIME_STORAGE_FORMAT;
            break;
        }
        // Adjust the date for storage.
        $date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
        $item['available_from'] = $date->format($format);
      }
      if (!empty($item['available_until']) && $item['available_until'] instanceof DrupalDateTime) {
        $date = $item['available_until'];
        switch ($this->getFieldSetting('datetime_type')) {
          case DateTimeItem::DATETIME_TYPE_DATE:
            // If this is a date-only field, set it to the default time so the
            // timezone conversion can be reversed.
            datetime_date_default_time($date);
            $format = DATETIME_DATE_STORAGE_FORMAT;
            break;

          default:
            $format = DATETIME_DATETIME_STORAGE_FORMAT;
            break;
        }
        // Adjust the date for storage.
        $date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
        $item['available_until'] = $date->format($format);
      }
    }
    return $values;
  }

}
