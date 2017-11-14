<?php

namespace Drupal\store;

use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class PriceRule {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The PriceFactory.
   *
   * @var \Drupal\store\PriceFactory;
   */
  protected $priceFactory;

  /**
   * @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage
   */
  protected $expressionLanguage;

  /**
   * Price rule constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager
   * @param \Drupal\store\PriceFactory $price_factory
   * @param \Symfony\Component\ExpressionLanguage\ExpressionLanguage $expression_language
   */
  public function __construct(EntityTypeManager $entity_type_manager, PriceFactory $price_factory, ExpressionLanguage $expression_language) {
    $this->entityTypeManager = $entity_type_manager;
    $this->priceFactory = $price_factory;
    $this->expressionLanguage = $expression_language;
  }

  /**
   * Returns updated price by existing price rules.
   *
   * @param string $type
   * @param Price $price
   * @param $data
   * @return array
   */
  public function updatePrice(string $type, Price $price, $data) {
    $appliedRules = [];
    if (isset($type) && isset($price)) {
      $priceRuleStorage = $this->entityTypeManager->getStorage('price_rule');
      $query = $priceRuleStorage->getQuery();
      $query->condition('price_rule_type', $type);
      $query->sort('weight', 'ASC');
      $entityIds = $query->execute();
      if (!empty($entityIds)) {
        $entities = $priceRuleStorage->loadMultiple($entityIds);
        /** @var \Drupal\store\Entity\PriceRule $entity */
        foreach ($entities as $entity) {
          $taxValue = $entity->getTaxValue();
          if ($this->expressionLanguage->evaluate($entity->getCondition(), $data)) {
            switch ($entity->getTaxType()) {
              case 'fixed':
                $taxPrice = $this->priceFactory->get((string)$taxValue, $entity->getTaxValueCurrency());
                $price = $price->add($taxPrice);
                $appliedRules[] = $entity->id();
                break;
              case 'rate':
                $price = $price->multiply($taxValue);
                $appliedRules[] = $entity->id();
                break;
            }
          }
        }
      }
    }

    return ['price' => $price, 'applied_rules' => $appliedRules];
  }

}
