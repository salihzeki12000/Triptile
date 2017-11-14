<?php

namespace Drupal\master\Action;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase as VBO;

abstract class ViewsBulkOperationsActionBase extends VBO implements ViewsBulkOperationsActionBaseInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'value' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    $results = [];
    foreach ($objects as $entity) {
      $results[] = $this->execute($entity);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $entityType = $this->getPluginDefinition()['type'];
    $entityBundle = $this->getEntityBundle();
    $entity = $this->entityTypeManager->getStorage($entityType)->create();
    $entityFormDisplay = $this->entityTypeManager->getStorage('entity_form_display')->load("$entityType.$entityBundle.vbo");
    $form['#parents'] = [];
    if ($widget = $entityFormDisplay->getRenderer($this->getFieldName())) {
      $items = $entity->get($this->getFieldName());
      $form += $widget->form($items, $form, $form_state);
      $form['#access'] = $items->access('edit');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['value'] = $form_state->getValue($this->getFieldName());
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\Core\Entity\ContentEntityBase $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

  abstract public function getEntityBundle();

  abstract public function getFieldName();

  abstract public function execute();

}
