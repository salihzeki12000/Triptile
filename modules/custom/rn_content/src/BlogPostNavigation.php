<?php

namespace Drupal\rn_content;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Entity\EntityTypeManager;

class BlogPostNavigation implements BlogPostNavigationInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface|object
   */
  protected $nodeStorage;

  /**
   * Constructor.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  public function getBlogCategoryId() {
    /* @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::request()->attributes->get('node');
    if ($node && $node->getType() == 'blog') {
      $category = $node->get('blog_categories')->first();
      if ($category && $category_id = $category->getValue()) {
        return $category_id['target_id'];
      }
    }
    return false;
  }

  public function generateBackLink() {
    if ($this->getBlogCategoryId()) {
      return $this->generateLink(t('Back'), Url::fromRoute('entity.taxonomy_term.canonical', array('taxonomy_term' => $this->getBlogCategoryId())));
    }
    return false;
  }

  public function generatePreviousLink() {
    return $this->generateBlogPostLink('<', 'DESC', '');
  }

  public function generateNextLink() {
    return $this->generateBlogPostLink('>', 'ASC', '');
  }

  protected function generateLink($text, $url) {
    return Link::fromTextAndUrl($text, $url)->toRenderable();
  }

  protected function generateBlogPostLink($condition, $sort, $text) {
    $nodes = $this->getBlogPostIdByDate($condition, $sort);
    if (!empty($nodes)) {
      return $this->generateLink($text, Url::fromRoute('entity.node.canonical', array('node' => reset($nodes))));
    }
    return false;
  }

  protected function getBlogPostIdByDate($condition, $sort) {
    /* @var \Drupal\node\Entity\Node $node */
    if ($node = \Drupal::request()->attributes->get('node')) {
      return $this->nodeStorage
        ->getQuery()
        ->condition('type', 'blog')
        ->condition('blog_categories.target_id', $this->getBlogCategoryId())
        ->condition('created', $node->getCreatedTime(), $condition)
        ->sort('created', $sort)
        ->range(0, 1)
        ->execute();
    }
    return false;
  }
}