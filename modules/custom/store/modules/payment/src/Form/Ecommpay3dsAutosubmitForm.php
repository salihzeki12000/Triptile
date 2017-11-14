<?php

namespace Drupal\payment\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Ecommpay3dsAutosubmitForm extends FormBase {

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Ecommpay3dsAutosubmitForm constructor.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStore = $temp_store_factory->get('payment');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('user.private_tempstore'));
  }

  /**
   * Checks if the current user has access to the form.
   */
  public function access() {
    return AccessResult::allowedIf($this->tempStore->get('ecommpay.pa_req') && $this->tempStore->get('ecommpay.acs_url') && $this->tempStore->get('ecommpay.return_url'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_ecommpay_3ds_autosubmit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#action'] = $this->tempStore->get('ecommpay.acs_url');
    $form['#attributes']['class'][] = 'payment-autosubmit';
    $form['#attached']['library'][] = 'payment/autosubmit-form';
    $form['#cache']['max-age'] = 0;

    $form['PaReq'] = [
      '#type' => 'hidden',
      '#value' => $this->tempStore->get('ecommpay.pa_req'),
    ];

    $form['TermUrl'] = [
      '#type' => 'hidden',
      '#value' => $this->tempStore->get('ecommpay.return_url'),
    ];

    $form['MD'] = [
      '#type' => 'hidden',
      '#value' => $this->tempStore->get('ecommpay.md'),
    ];

    $form['message'] = [
      '#markup' => '<div class="payment-redirect-message">'
        . $this->t('You\'ll be redirected to your bank page in 3 seconds to complete the payment...', [], ['context' => 'Payment autosubmit form'])
        . '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do nothing. The form is not expected to be submitted on this site.
  }

}
