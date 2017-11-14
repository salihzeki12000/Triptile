<?php

namespace Drupal\store\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\store\Price;
use Drupal\user\UserInterface;

/**
 * Provides an interface for defining Store order entities.
 *
 * @ingroup store
 */
interface StoreOrderInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Store order type.
   *
   * @return string
   *   The Store order type.
   */
  public function getType();

  /**
   * Gets the Store order creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Store order.
   */
  public function getCreatedTime();

  /**
   * Sets the Store order creation timestamp.
   *
   * @param int $timestamp
   *   The Store order creation timestamp.
   *
   * @return \Drupal\store\Entity\StoreOrderInterface
   *   The called Store order entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Sets order owner.
   *
   * @param \Drupal\user\UserInterface $owner
   * @return static
   */
  public function setOwner(UserInterface $owner);

  /**
   * Sets order owner uid.
   *
   * @param int $uid
   * @return static
   */
  public function setOwnerId($uid);

  /**
   * Gets order owner.
   *
   * @return \Drupal\user\Entity\User
   */
  public function getOwner();

  /**
   * Gets order number.
   *
   * @return string
   */
  public function getOrderNumber();

  /**
   * Set order number.
   *
   * @param string $number
   * @return static
   */
  public function setOrderNumber($number);

  /**
   * Sets site code.
   *
   * @param string $code
   * @return static
   */
  public function setSiteCode($code);

  /**
   * Gets site code.
   *
   * @return string
   */
  public function getSiteCode();

  /**
   * Gets order status.
   *
   * @return string
   */
  public function getStatus();

  /**
   * Sets order status.
   *
   * @param string $status
   * @return static
   */

  public function setStatus($status);

  /**
   * Gets order state.
   *
   * @return int
   */
  public function getState();

  /**
   * Gets order hash.
   *
   * @return string
   */
  public function getHash();

  /**
   * Sets order hash.
   *
   * @param string $hash
   * @return static
   */
  public function setHash($hash);

  /**
   * Gets order trip type.
   *
   * @return string
   */
  public function getTripType();

  /**
   * Sets order trip type.
   *
   * @param string $trip_type
   * @return static
   */
  public function setTripType($trip_type);

  /**
   * Gets the order total cost.
   *
   * @return \Drupal\store\Price
   */
  public function getOrderTotal();

  /**
   * Sets the order total cost.
   *
   * @param \Drupal\store\Price $total
   * @return static
   */
  public function setOrderTotal(Price $total);

  /**
   * Sets the order train tickets.
   *
   * @param array $tids
   *   The array of train ticket IDs.
   *
   * @return \Drupal\store\Entity\StoreOrderInterface
   *   The called Store order entity.
   */
  public function setTickets($tids);

  /**
   * Gets list of tickets attached to the train order.
   *
   * @return \Drupal\train_base\Entity\TrainTicket[]
   */
  public function getTickets();

  /**
   * Gets all invoices attached to the order ordered by id.
   *
   * @return \Drupal\store\Entity\InvoiceInterface[]
   */
  public function getInvoices();

  /**
   * Gets array of pdf files
   *
   * @return \Drupal\File\Entity\File []
   */

  public function getPdfFiles();
  
  /**
   * Gets notes from train order.
   *
   * @return string
   */
  public function getNotes();

  /**
   * Sets notes on a train order.
   *
   * @param string $notes
   * @return static
   */
  public function setNotes($notes);

  /**
   * Gets all related order items.
   *
   * @return \Drupal\store\Entity\OrderItem[]
   */
  public function getOrderItems();

  /**
   * Gets Ticket issue date from train order.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  public function getTicketIssueDate();

  /**
   * Sets Ticket issue date on a train order.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $ticket_issue_date
   * @return static
   */
  public function setTicketIssueDate(DrupalDateTime $ticket_issue_date);

  /**
   * Set train providers, which are executed for this order.
   *
   * @param array $trainProviders
   * @return static
   */
  public function setTrainProviders($trainProviders);

  /**
   * Get train providers, which are executed for this order.
   *
   * @return array
   */
  public function getTrainProviders();


}
