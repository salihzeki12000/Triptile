<?php

namespace Drupal\booking;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Utility\Random;
use Drupal\rn_user\SessionStore;
use Drupal\rn_user\SessionStoreException;
use Drupal\store\Entity\Invoice;
use Drupal\store\Entity\OrderItem;
use Drupal\store\Entity\StoreOrder;
use Drupal\store\OrderVerification;
use Drupal\train_booking\TrainBookingManager;
use Drupal\user\Entity\User;
use Drupal\store\PriceRule;
use Drupal\salesforce\SalesforceSync;
use Drupal\salesforce\Plugin\SalesforceMappingManager;
use Drupal\salesforce\Entity\MappableEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\HttpKernel\HttpCache\Store;

/**
 * Class BookingManagerBase.
 *
 * @package Drupal\booking
 */
abstract class BookingManagerBase implements BookingManagerInterface {

  use StringTranslationTrait;

  /**
   * Session store keys
   */
  const
    ORDER_KEY = 'order',
    ORDER_ITEMS_KEY = 'order_items',
    INVOICE_KEY = 'invoice',
    USER_KEY = 'user',
    USER_CURRENCY_KEY = 'user_currency';

  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\store\Entity\StoreOrder
   */
  protected $order;

  /**
   * @var \Drupal\store\Entity\OrderItem[]
   */
  protected $orderItems;

  /**
   * @var \Drupal\user\Entity\User;
   */  /**
   * @var \Drupal\user\Entity\User;
   */
  protected $user;

  /**
   * @var \Drupal\store\Entity\Invoice
   */
  protected $invoice;

  /**
   * @var \Drupal\store\PriceRule
   */
  protected $priceRule;

  /**
   * Session store. Use getStore method to access the store.
   *
   * @var \Drupal\rn_user\SessionStore
   */
  private $store;

  /**
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * @var \Drupal\salesforce\SalesforceSync
   */
  protected $salesforceSync;

  /**
   * @var \Drupal\salesforce\Plugin\SalesforceMappingManager
   */
  protected $mappingManager;

  /**
   * @var \Drupal\store\OrderVerification
   */
  protected $orderVerification;

  /**
   * BookingManagerBase constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\store\PriceRule $price_rule
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   * @param \Drupal\salesforce\SalesforceSync $salesforce_sync
   * @param \Drupal\salesforce\Plugin\SalesforceMappingManager $mapping_manager
   * @param \Drupal\store\OrderVerification $order_verification
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManager $entity_type_manager, PriceRule $price_rule, LanguageManager $language_manager, SalesforceSync $salesforce_sync, SalesforceMappingManager $mapping_manager, OrderVerification $order_verification) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->priceRule = $price_rule;
    $this->languageManager = $language_manager;
    $this->salesforceSync = $salesforce_sync;
    $this->mappingManager = $mapping_manager;
    $this->orderVerification = $order_verification;
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\booking\BookingManagerException
   */
  public function setStore(SessionStore $store) {
    try {
      $store->sessionExist();
    }
    catch (SessionStoreException $e) {
      throw new BookingManagerException('Session is invalid');
    }

    $this->store = $store;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderItems(array $order_items) {
    if (!empty($order_items)) {
      $this->orderItems = $order_items;
      $this->getStore()->set(static::ORDER_ITEMS_KEY, $order_items);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setInvoice(Invoice $invoice) {
    if (!empty($invoice)) {
      $this->invoice = $invoice;
      $this->getStore()->set(static::INVOICE_KEY, $invoice);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setOrder(StoreOrder $order) {
    if (!empty($order)) {
      $this->order = $order;
      $this->getStore()->set(static::ORDER_KEY, $order);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setUser(User $user) {
    if (!empty($user)) {
      $this->user = $user;
      $this->getStore()->set(static::USER_KEY, $user);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    if (!$this->order) {
      $this->order = $this->getStore()->get(static::ORDER_KEY) ? : $this->createOrder();
    }

    return $this->order;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItems() {
    $this->getOrder();
    if (!$this->orderItems) {
      $this->orderItems = $this->getStore()->get(static::ORDER_ITEMS_KEY);
    }

    return $this->orderItems;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    if (!$this->user) {
      $this->user = $this->createUser();
    }

    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getInvoice() {
    if (!$this->invoice) {
      $this->invoice = $this->getStore()->get(static::INVOICE_KEY) ? : $this->createInvoice();
    }
    if ($this->invoice->id()) {
      $this->invoice = $this->entityTypeManager->getStorage('invoice')->load($this->invoice->id());
    }

    return $this->invoice;
  }

  /**
   * {@inheritdoc}
   */
  public function convertInvoiceAmount() {
    $invoice = $this->getInvoice();
    $invoice->setAmount($this->getOrder()->getOrderTotal());
    $this->setInvoice($invoice);

    return $this->invoice;
  }

  /**
   * Creates a new order, order items and saves them in the database.
   *
   * @return \Drupal\store\Entity\StoreOrder
   */
  protected function createOrder() {
    $order = StoreOrder::create([
      'type' => $this->getOrderType(),
      'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
    ])
      ->setOwnerId($this->currentUser->id())
      ->setHash($this->getStore()->getSessionId());
    $timetableResult = $this->getStore()->get(TrainBookingManager::TIMETABLE_RESULT_KEY);
    /** @var \Drupal\train_provider\TrainInfoHolder $trainInfoHolder */
    $trainInfoHolder = $timetableResult[1]['train_info'];
    if ($ticketIssueDate = $trainInfoHolder->getTicketIssueDate()) {
      $order->setTicketIssueDate($ticketIssueDate);
    }
    $this->getStore()->set(static::ORDER_KEY, $order);

    $this->doCreateOrderItems($order);

    return $order;
  }

  /**
   * Creates an order item and attaches it to the order.
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @param string $type
   * @param array $values
   * @return \Drupal\store\Entity\OrderItem
   */
  protected function createOrderItem(StoreOrder $order, $type, $values = []) {
    $order_item = OrderItem::create([
      'type' => $type,
      'order_reference' => $order,
    ] + $values);

    return $order_item;
  }

  /**
   * Creates a user.
   *
   * @return \Drupal\user\Entity\User
   */
  public function createUser() {
    $email = $this->getStore()->get(TrainBookingManager::EMAIL_KEY);
    $user = user_load_by_mail($email);
    if (empty($user)){
      $random = new Random();
      $user = User::create([
        'name' => $email,
        'mail' => $email,
        'pass' => $random->string(8),
        'status' => 1,
      ]);
    }

    return $user;
  }

  /**
   * Creates Invoice related to current booking.
   *
   * @return \Drupal\store\Entity\Invoice
   */
  protected function createInvoice() {
    $invoice = Invoice::create()
      ->setStatus(Invoice::STATUS_UNPAID)
      ->setAmount($this->getOrder()->getOrderTotal())
      ->setVisibility(true);

    return $invoice;
  }

  /**
   * Calculates order total using attached order items and applies price rule.
   *
   * @param \Drupal\store\Entity\StoreOrder $order
   * @return static
   */
  public function calculateOrderTotal(StoreOrder $order) {
    $store = $this->getStore();
    $all_order_items = $this->getOrderItems();
    $total_price = null;
    /** @var \Drupal\store\Entity\OrderItem $order_item */
    foreach ($all_order_items as $route_key => $order_items) {
      foreach ($order_items as $order_item) {
        $price = $order_item->getPrice();
        $quantity = $order_item->getQuantity();
        $subtotal = $price->multiply($quantity);
        if (empty($total_price)) {
          $total_price = $subtotal;
        }
        else {
          /** @var \Drupal\store\Price $total_price */
          $total_price = $total_price->add($subtotal);
        }
      }
    }
    if (!empty($total_price)) {
      $order->setOrderTotal($total_price);
      $store->set('order', $order);
    }

    return $this;
  }

  /**
   * Loads entities
   *
   * @param string $entity_type
   * @param int|array $id
   * @return \Drupal\Core\Entity\EntityInterface[]|\Drupal\Core\Entity\EntityInterface
   */
  protected function loadEntity($entity_type, $id) {
    if (is_array($id)) {
      return $this->entityTypeManager->getStorage($entity_type)->loadMultiple($id);
    }
    else {
      return $this->entityTypeManager->getStorage($entity_type)->load($id);
    }
  }

  /**
   * Gets current session store.
   *
   * @return \Drupal\rn_user\SessionStore
   * @throws \Drupal\booking\BookingManagerException
   */
  protected function getStore() {
    if (is_null($this->store)) {
      throw new BookingManagerException('Session store is not set.');
    }

    return $this->store;
  }

  /**
   * {@inheritdoc}
   */
  public function bookingPaid(StoreOrder $order) {
    $status = $this->orderVerification->doSetForManualVerification($order) ? StoreOrder::STATUS_VERIFICATION : StoreOrder::STATUS_PROCESSING;
    $order->setStatus($status)->save();
    $this->salesforceBaseTrigger($order);
  }

  /**
   * {@inheritdoc}
   */
  public function bookingFailed(StoreOrder $order) {
    $order->setStatus(StoreOrder::STATUS_FAILED)->save();
    $this->salesforceBaseTrigger($order);
  }

  /**
   * {@inheritdoc}
   */
  public function bookingCanceled(StoreOrder $order) {
    $order->setStatus(StoreOrder::STATUS_FAILED)->save();
    $this->salesforceBaseTrigger($order);
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface
   */
  protected function salesforceBaseTrigger(ContentEntityInterface $entity) {
    $this->salesforceSync->entityCrud($entity, SalesforceSync::OPERATION_UPDATE);
  }

  abstract protected function getOrderType();

  abstract protected function doCreateOrderItems(StoreOrder $order);

}
