<?php

/**
 * @file
 * Provides Drupal\train_provider\TrainProviderInterface
 */

namespace Drupal\train_provider;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for train provider plugins.
 */
interface TrainProviderInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Return the name of the train provider.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Returns timetable.
   *
   * @return array
   */
  public function getTimeTable();

  /**
   * Checks if train provider is enabled.
   *
   * @return bool
   */
  public function isEnabled();

  /**
   * Get max needed number of days before departure for current train provider.
   *
   * @return mixed
   */
  public function getMaxDaysBeforeDeparture();

  /**
   * Get min needed number of days before departure for current train provider.
   *
   * @return mixed
   */
  public function getMinDaysBeforeDeparture();

  /**
   * Get min needed number of hours before departure for current train provider.
   *
   * @return mixed
   */
  public function getMinHoursBeforeDeparture();

  /**
   * Get min needed number of days before departure for all train providers.
   *
   * @return mixed
   */
  public function getCommonMinDaysBeforeDeparture();

  /**
   * Get min needed number of hours before departure for all train providers.
   *
   * @return mixed
   */
  public function getCommonMinHoursBeforeDeparture();

}
