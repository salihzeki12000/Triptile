<?php
 
/**
 * @file
 * Contains \Drupal\train_provider\SearchPage.
 */
 
namespace Drupal\train_provider\Controller;
 
use Drupal\Core\Controller\ControllerBase;
use Drupal\train_provider\TrainSearcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
 
class SearchPage extends ControllerBase {

  /**
   * @var \Drupal\train_provider\TrainSearcher
   */
  protected $trainSearcher;
 
  /**
   * {@inheritdoc}
   */
  public function __construct(TrainSearcher $trainSearcher) {
    $this->trainSearcher = $trainSearcher;
  }
 
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('train_provider.train_searcher')
    );
  }
 
  public function getPage($session_id) {
    $form = \Drupal::formBuilder()->getForm('Drupal\train_booking\Form\TimetableForm', $session_id);
    return $form;
  }
}