<?php

namespace Drupal\tt_constructor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\salesforce\SalesforceApi;
use Drupal\salesforce\SelectQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConstructorPage extends ControllerBase{

  /**
   * @var \Drupal\Core\Database\Connection.
   */
  public $sApi;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    // Метод get() принимает уникальный идентификатор сервиса в качестве параметра.
      $container->get('salesforce_api')
    );
  }

  /**
   * AwesomeRouteController constructor.
   *
   * @param \Drupal\Core\Database\Connection $database.
   */
  public function __construct(SalesforceApi $sApi) {
    // Передадим объект в определённое ранее свойство.
    $this->sApi = $sApi;
  }


  /**
   * Display markup for step 2 constructor
   * 
   * @return array
   */
  public function content() {
    $build = array(
      '#theme' => 'tt_constructor',
      '#show_tips' => $_COOKIE['show_tips'] ?? 1,
    );
    return $build;
  }

  /**
   * Display markup for itinerary
   * Load order to cookies
   *
   * @return array
   */
  public function itinerary($order_hash) {
    $query = \Drupal::database()->select('trip_order', 't');
    $query->addField('t', 'order_object__value');
    $query->condition('t.hash', $order_hash);
    $query->range(0, 1);
    $order_object = $query->execute()->fetchField();

    if(!$order_object){
      throw new NotFoundHttpException();
    }
    else{
      $build = array(
        '#theme' => 'tt_itinerary',
        '#order_hash' => $order_hash,
      );
      $build['#attached']['drupalSettings']['orderObject'] = $order_object;
      return $build;
    }
  }

  /**
   * Display markup for itinerary map
   * Load order to cookies
   *
   * @return array
   */
  public function itineraryMap() {
    $build = array(
      '#theme' => 'tt_itinerary_map',
    );
    $output = \Drupal::service('renderer')->renderRoot($build);

    $response = new Response();
    $response->setContent($output);
    return $response;
  }

  /**
   * Return HTML for edit Step popup
   *
   * @return array
   */
  public function editStepPopup() {

    $build = array(
      '#theme' => 'tt_constructor_edit_step_popup',
    );
    $output = \Drupal::service('renderer')->renderRoot($build);

    $response = new Response();
    $response->setContent($output);
    return $response;
  }

  /**
   * Return HTML for edit Hub popup
   *
   * @return array
   */
  public function editHubPopup() {

    $build = array(
      '#theme' => 'tt_constructor_edit_hub_popup',
    );
    $output = \Drupal::service('renderer')->renderRoot($build);

    $response = new Response();
    $response->setContent($output);
    return $response;
  }

  /**
   * Return HTML for edit Hotel popup
   *
   * @return array
   */
  public function editHotelPopup() {

    $build = array(
      '#theme' => 'tt_constructor_edit_hotel_popup',
    );
    $output = \Drupal::service('renderer')->renderRoot($build);

    $response = new Response();
    $response->setContent($output);
    return $response;
  }

  /**
   * Return HTML for edit Transfer popup
   *
   * @return array
   */
  public function editTransferPopup() {

    $build = array(
      '#theme' => 'tt_constructor_edit_transfer_popup',
    );
    $output = \Drupal::service('renderer')->renderRoot($build);

    $response = new Response();
    $response->setContent($output);
    return $response;
  }

  /**
   * Return HTML for edit Activity popup
   *
   * @return array
   */
  public function editActivityPopup() {

    $build = array(
      '#theme' => 'tt_constructor_edit_activity_popup',
    );
    $output = \Drupal::service('renderer')->renderRoot($build);

    $response = new Response();
    $response->setContent($output);
    return $response;
  }

  /**
   * Return HTML for edit Connection popup
   *
   * @return array
   */
  public function editConnectionPopup() {

    $build = array(
      '#theme' => 'tt_constructor_edit_connection_popup',
    );
    $output = \Drupal::service('renderer')->renderRoot($build);

    $response = new Response();
    $response->setContent($output);
    return $response;
  }

  /**
   * Return HTML for save & share popup
   *
   * @return array
   */
  public function editSaveSharePopup() {

    $build = array(
      '#theme' => 'tt_itinerary_save_share_popup',
    );
    $output = \Drupal::service('renderer')->renderRoot($build);

    $response = new Response();
    $response->setContent($output);
    return $response;
  }

  /**
   * Return HTML for book now popup
   *
   * @return array
   */
  public function editBookNowPopup() {

    $build = array(
      '#theme' => 'tt_itinerary_book_now_popup',
    );
    $output = \Drupal::service('renderer')->renderRoot($build);

    $response = new Response();
    $response->setContent($output);
    return $response;
  }

}