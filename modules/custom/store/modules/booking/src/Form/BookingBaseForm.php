<?php

namespace Drupal\booking\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal;

/**
 * Class BookingBaseForm.
 *
 * @package Drupal\booking\Form
 */
abstract class BookingBaseForm extends FormBase  {

  /**
   * Booking session life time.
   * @todo Make it configurable.
   */
  const SESSION_LIFE_TIME = 1800;

  /**
   * The session store service.
   *
   * @var \Drupal\rn_user\SessionStoreFactory
   */
  protected $sessionStoreFactory;

  /**
   * The session manager service.
   *
   * @var \Drupal\Core\Session\SessionManager
   */
  protected $sessionManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Session store instance.
   *
   * @var \Drupal\rn_user\SessionStore
   */
  protected $store;


  /**
   * user TempStore
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */

  protected $userPrivateTempStore;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Default Currency service.
   *
   * @var \Drupal\store\DefaultCurrency
   */
  protected $defaultCurrency;

  /**
   * The Train Booking Logger service.
   *
   * @var \Drupal\train_booking\TrainBookingLogger
   */
  protected $trainBookingLogger;

  /**
   * BookingBaseForm constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  public function __construct(ContainerInterface $container) {
    $this->sessionStoreFactory = $container->get('rn_user.session_store');
    $this->sessionManager = $container->get('session_manager');
    $this->entityTypeManager = $container->get('entity_type.manager');
    $this->currentUser = $container->get('current_user');
    $this->store = $this->sessionStoreFactory->get($this->getCollectionName());
    $this->userPrivateTempStore = $container->get('user.private_tempstore')->get('store');
    $this->configFactory = $container->get('config.factory');
    $this->defaultCurrency = $container->get('store.default_currency');
    $this->trainBookingLogger = $container->get('train_booking.logger');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->startSession();
    return $form;
  }

  /**
   * Loads entity.
   *
   * @param string $entity_type
   * @param integer $id
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function loadEntity($entity_type, $id) {
    return $this->entityTypeManager->getStorage($entity_type)->load($id);
  }

  /**
   * Initiates session for anonymous users.
   *
   * @return static
   */
  protected function startSession() {
    // Start a session for anonymous users.
    if ($this->currentUser->isAnonymous() && !isset($_SESSION['session_started'])) {
      $_SESSION['session_started'] = TRUE;
      $this->sessionManager->start();
    }

    return $this;
  }

  /**
   * Return home route.
   *
   * @return string
   */
  protected function getHomeRoute() {
    $language = Drupal::languageManager()->getCurrentLanguage();
    $url = Url::fromRoute('<front>', [], ['language' => $language]);
    $route = $url->getRouteName();

    return $route;
  }



  /**
   * Gets collection name used to store values in the session store.
   *
   * @return string
   */
  abstract protected function getCollectionName();

}
