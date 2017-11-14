<?php

namespace Drupal\train_booking\Controller;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\salesforce\SalesforceSync;
use Drupal\store\Entity\StoreOrder;
use Drupal\train_booking\Form\SaveSearchForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;


/**
 * Gets station like a JSON response
 *
 */
class AjaxController extends ControllerBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * @var \Drupal\salesforce\SalesforceSync
   */
  protected $salesforceSync;

  /**
   * Constructs a new AjaxController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\salesforce\SalesforceSync $salesforce_sync
   */
  public function __construct(EntityTypeManager $entity_type_manager, QueryFactory $query_factory, SalesforceSync $salesforce_sync) {
    $this->queryFactory = $query_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->salesforceSync = $salesforce_sync;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.query'),
      $container->get('salesforce_sync')
    );
  }

  /**
   * Returns a set of nodes' last read timestamps.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function getStations(Request $request, string $q) {
    $links = [];
    $query = $this->queryFactory->get('station');
    $query->condition('status', 1);
    $query->condition('name', $q, 'CONTAINS');
    $query->notExists('parent_station');
    $query->range(0, 10);
    $stations = $query->execute();
    if ($stations) {
      $stations = $this->entityTypeManager->getStorage('station')->loadMultiple($stations);
    }
    foreach ($stations as $station) {
      $links[] = ['id' => $station->id(), 'name' => $station->getName()];
    }
    return new JsonResponse($links);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $coach_class_id
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function showGallery(Request $request, $coach_class_id) {
    $response = new AjaxResponse();

    if(!empty($coach_class_id)) {
      /** @var \Drupal\train_base\Entity\CoachClass $coach_class */
      if( $coach_class = $this->entityTypeManager->getStorage('coach_class')->load($coach_class_id)) {
        $coach_class_info = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'coach-class-info'
            ]
          ]
        ];
        $coach_class_info['gallery'] = views_embed_view('coach_class_gallery', 'coach_class_gallery');
        $coach_class_info['coach_class_name'] = [
          '#type' => 'container',
          '#markup' => $coach_class->getName(),
            '#attributes' => [
              'class' => [
                'coach-class-name'
              ]
            ]
          ];

        $coach_class_info['coach_class_description'] = [
          '#type' => 'container',
          '#markup' => $coach_class->getDescription(),
          '#attributes' => [
            'class' => [
              'coach-class-description'
            ]
          ]
        ];

        $options = [
          'dialogClass' => 'coach-class-popup',
          'width' => '600px',
          'draggable' => false
        ];

        $response->addCommand(new OpenModalDialogCommand(null, $coach_class_info, $options));
      }
    }

    return $response;
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function saveSearch(Request $request) {
    $response = new AjaxResponse();

    $form = \Drupal::formBuilder()->getForm(SaveSearchForm::class);

    $options = [
      'dialogClass' => 'save-search-popup',
      'width' => '600px',
      'draggable' => false
    ];

    $response->addCommand(new OpenModalDialogCommand(null, $form, $options));

    return $response;
  }

  /**
   * Track ticket download.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @param StoreOrder $order_hash
   * @return \Symfony\Component\HttpFoundation\JsonResponse The JSON response.
   * The JSON response.
   */
  public function trackTicketDownload(Request $request, StoreOrder $order_hash) {
    $order = $order_hash;
    $trackTicketDownload = [];

    if (!$order->getData('track_ticket_download')) {
      $trackTicketDownload = [
        'ip' => $request->getClientIp(),
        'timestamp' => DrupalDateTime::createFromTimestamp(time(), 'Europe/Minsk')->format('Y-d-m H:m:i'),
      ];
      $order->setData('track_ticket_download', $trackTicketDownload);
      $order->save();
      $this->salesforceSync->entityCrud($order, SalesforceSync::OPERATION_UPDATE);
    }

    return new JsonResponse($trackTicketDownload);
  }

}
