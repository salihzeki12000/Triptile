<?php

namespace Drupal\payment\Form\Admin;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Plugin\PaymentMethodManager;
use Drupal\plugin\PluginDefinition\ArrayPluginDefinitionDecorator;
use Drupal\plugin\PluginDefinition\PluginOperationsProviderDefinitionInterface;
use Drupal\plugin\PluginDiscovery\TypedDefinitionEnsuringPluginDiscoveryDecorator;
use Drupal\plugin\PluginType\PluginTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentMethodsForm extends FormBase {

  /**
   * @var \Drupal\payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * @var \Drupal\plugin\PluginType\PluginTypeManager
   */
  protected $pluginTypeManager;

  /**
   * @var \Drupal\Core\DependencyInjection\ClassResolver
   */
  protected $classResolver;

  /**
   * PaymentMethodsForm constructor.
   *
   * @param \Drupal\payment\Plugin\PaymentMethodManager $payment_method_manager
   * @param \Drupal\plugin\PluginType\PluginTypeManager $plugin_type_manager
   * @param \Drupal\Core\DependencyInjection\ClassResolver $class_resolver
   */
  public function __construct(PaymentMethodManager $payment_method_manager, PluginTypeManager $plugin_type_manager, ClassResolver $class_resolver) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->pluginTypeManager = $plugin_type_manager;
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.payment.payment_method'),
      $container->get('plugin.plugin_type_manager'),
      $container->get('class_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_methods_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo Is there a better way to get definitions?
    $plugin_discovery = new TypedDefinitionEnsuringPluginDiscoveryDecorator($this->pluginTypeManager->getPluginType('payment_method'));
    $payment_methods = $plugin_discovery->getDefinitions();

    $form['payment_methods'] = array(
      '#header' => array($this->t('Title'), $this->t('Enabled'), $this->t('Weight'), $this->t('Operations')),
      '#tabledrag' => array(array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'form-select',
      )),
      '#type' => 'table',
    );

    foreach ($payment_methods as $payment_method => $definition) {
      $configs = $this->configFactory()->get('plugin.plugin_configuration.payment_method.' . $payment_method);

      $form['payment_methods'][$payment_method] = array(
        '#attributes' => array(
          'class' => array('draggable'),
        ),
        '#weight' => $configs->get('weight'),
      );
      $form['payment_methods'][$payment_method]['label'] = array(
        '#description' => $definition['description'],
        '#markup' => $definition['label'],
        '#title' => $this->t('Title'),
        '#title_display' => 'invisible',
        '#type' => 'item',
      );
      $form['payment_methods'][$payment_method]['status'] = array(
        '#default_value' => $configs->get('status'),
        '#title' => $this->t('Enabled'),
        '#title_display' => 'invisible',
        '#type' => 'checkbox',
      );
      $form['payment_methods'][$payment_method]['weight'] = array(
        '#default_value' => $configs->get('weight'),
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#type' => 'weight',
      );
      $links = [];
      if ($definition instanceof PluginOperationsProviderDefinitionInterface) {
        $operations_provider = $this->classResolver->getInstanceFromDefinition($definition->getOperationsProviderClass());
        $links = $operations_provider->getOperations($payment_method);
      };
      $form['payment_methods'][$payment_method]['operations'] = array(
        '#links' => $links,
        '#title' => $this->t('Operations'),
        '#type' => 'operations',
      );
    }

    uasort($form['payment_methods'], [SortArray::class, 'sortByWeightProperty']);

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('payment_methods') as $payment_method => $values) {
      $configs = $this->configFactory()->get('plugin.plugin_configuration.payment_method.' . $payment_method)->get();
      /** @var \Drupal\payment\Plugin\PaymentMethod\PaymentMethodInterface $plugin */
      $plugin = $this->paymentMethodManager->createInstance($payment_method, $configs);
      $configs = $plugin->getConfiguration();
      $configs['weight'] = $values['weight'];
      $configs['status'] = (bool) $values['status'];
      $this->configFactory()
        ->getEditable('plugin.plugin_configuration.payment_method.' . $payment_method)
        ->setData($configs)
        ->save();
    }
  }

}
