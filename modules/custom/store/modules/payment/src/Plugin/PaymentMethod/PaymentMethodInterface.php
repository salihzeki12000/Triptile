<?php

namespace Drupal\payment\Plugin\PaymentMethod;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Url;
use Drupal\payment\Entity\TransactionInterface;
use Drupal\store\Entity\InvoiceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for Payment method plugins.
 */
interface PaymentMethodInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Gets payment method weight.
   *
   * @return integer
   */
  public function getWeight();

  /**
   * Checks if current payment method is enabled.
   *
   * @return bool
   */
  public function isEnabled();

  /**
   * Checks if current payment method is top.
   *
   * @return bool
   */
  public function isTop();

  /**
   * Builds payment form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function buildPaymentDataForm(array $form, FormStateInterface $form_state);

  /**
   * Builds billing profile form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function buildBillingProfileForm(array $form, FormStateInterface $form_state);

  /**
   * Process validation on payment form data.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return static
   */
  public function validatePaymentDataForm(array $form, FormStateInterface $form_state);

  /**
   * Process validation on billing profile form data.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return static
   */
  public function validateBillingProfileForm(array $form, FormStateInterface $form_state);

  /**
   * Submit payment data form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return static
   */
  public function submitPaymentDataForm(array $form, FormStateInterface $form_state);

  /**
   * Submit billing profile form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return static
   */
  public function submitBillingProfileForm(array $form, FormStateInterface $form_state);

  /**
   * Sets payment data needed for payment.
   *
   * @param array $payment_data
   * @return static
   */
  public function setPaymentData(array $payment_data);

  /**
   * Sets billing data.
   *
   * @param array $billing_data
   * @return static
   */
  public function setBillingData(array $billing_data);

  /**
   * Sets the url where user will be redirected after successful payment.
   *
   * @param \Drupal\Core\Url $success_url
   * @return static
   */
  public function setSuccessUrl(Url $success_url);

  /**
   * Sets the url where user will be redirected if he cancel the payment.
   *
   * @param \Drupal\Core\Url $cancel_url
   * @return static
   */
  public function setCancelUrl(Url $cancel_url);

  /**
   * Sets the url where user will be redirected if payment fails.
   *
   * @param \Drupal\Core\Url $fail_url
   * @return static
   */
  public function setFailUrl(Url $fail_url);

  /**
   * Sets the invoice that is has to be processed.
   *
   * @param \Drupal\store\Entity\InvoiceInterface $invoice
   * @return static
   */
  public function setInvoice(InvoiceInterface $invoice);

  /**
   * Gets the invoice that is processed.
   *
   * @return \Drupal\store\Entity\InvoiceInterface
   */
  public function getInvoice();

  /**
   * Starts payment process.
   *
   * @return \Drupal\Core\Url
   */
  public function processPayment();

  /**
   * Notifies that user returned to site.
   *
   * @return static
   */
  public function paymentReturned();

  /**
   * Notifies that payment was canceled.
   *
   * @return static
   */
  public function paymentCanceled();

  /**
   * Notifies that payment has been failed.
   *
   * @return static
   */
  public function paymentFailed();

  /**
   * Checks if the invoice has been paid.
   *
   * @return bool
   */
  public function invoiceIsPaid();

  /**
   * Processes request from payment server to update transaction info.
   *
   * @param \Drupal\payment\Entity\TransactionInterface $transaction
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return static
   */
  public function processTransactionUpdateRequest(TransactionInterface $transaction, Request $request);

}
