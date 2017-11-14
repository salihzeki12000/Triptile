<?php

namespace Drupal\master\Entity;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityAutocompleteMatcher;

class EntityAutocompleteMatcherCustom extends EntityAutocompleteMatcher {

  /*
   * {@inheritdoc]
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {

    $matches = array();

    $options = array(
      'target_type' => $target_type,
      'handler' => $selection_handler,
      'handler_settings' => $selection_settings,
    );
    $handler = $this->selectionManager->getInstance($options);

     switch ($target_type) {
       case 'coach_class' || 'train':
         $limit = 30;
         break;
       default:
         $limit = 10;
    }

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      // Changing limit from 10 to 50.
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, $limit);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          $key = "$label ($entity_id)";
          // Strip things like starting/trailing white spaces, line breaks and
          // tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          $matches[] = array('value' => $key, 'label' => $label);
        }
      }
    }

    return $matches;
  }
}