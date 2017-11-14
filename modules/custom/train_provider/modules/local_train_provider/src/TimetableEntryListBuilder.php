<?php

namespace Drupal\local_train_provider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Timetable entry entities.
 *
 * @ingroup local_train_provider
 */
class TimetableEntryListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Timetable entry ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\local_train_provider\Entity\TimetableEntry */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.timetable_entry.edit_form', array(
          'timetable_entry' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $query = \Drupal::destination()->getAsArray();
    $operations['toggle'] = [
      'title' => $entity->isEnabled() ? $this->t('Disable') : $this->t('Enable'),
      'weight' => 50,
      'url' => Url::fromRoute('entity.timetable_entry.toggle', ['timetable_entry' => $entity->id()], ['query' => $query]),
    ];

    return $operations;
  }

}
