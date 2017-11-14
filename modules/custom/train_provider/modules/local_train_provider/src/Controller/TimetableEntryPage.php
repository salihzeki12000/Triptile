<?php

namespace Drupal\local_train_provider\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\local_train_provider\Entity\TimetableEntryInterface;

class TimetableEntryPage extends ControllerBase {



  public function toggle(TimetableEntryInterface $timetable_entry) {
    $timetable_entry->setStatus(!$timetable_entry->isEnabled())
      ->save();

    if ($this->getRedirectDestination()->get()) {
      $url = Url::fromUserInput(\Drupal::destination()->get());
      return $this->redirect($url->getRouteName(), $url->getRouteParameters());
    }
    else {
      return $this->redirect($timetable_entry->toUrl());
    }
  }

}
