<?php

namespace Drupal\rn_content;

/**
 * Interface BlogPostNavigationInterface.
 *
 * @package Drupal\rn_content
 */
interface BlogPostNavigationInterface {

  /**
   * Gets blog category id
   * @return mixed
   */
  public function getBlogCategoryId();

  /**
   * Generates link to blog category page
   * @return mixed
   */
  public function generateBackLink();

  /**
   * Generates link to previous blog post in category
   * @return mixed
   */
  public function generatePreviousLink();

  /**
   * Generates link to next blog post in category
   * @return mixed
   */
  public function generateNextLink();
}
