<?php

namespace Drupal\salesforce\ComputedField;

use Drupal\Core\Field\FieldItemList;

class MappingObjectNameComputed extends FieldItemList  {

  public function getValue($include_computed = FALSE) {
    /** @var \Drupal\salesforce\Entity\SalesforceMappingObjectInterface $mappingObject */
    $mappingObject = $this->getEntity();
    return $mappingObject->getMappedEntityTypeId() . ' ' . $mappingObject->getMappedEntityId()
      . ' - ' . $mappingObject->getSalesforceObject() . ' ' . $mappingObject->getRecordId();
  }

}
