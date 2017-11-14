<?php

namespace Drupal\rn_user\Entity;

use Drupal\salesforce\Entity\MappableEntityInterface;
use Drupal\salesforce\Entity\MappableEntityTrait;
use Drupal\user\Entity\User as UserBase;

/**
 * Class User
 *
 * Replaces Drupal core user entity class in order to add custom methods.
 *
 * @package Drupal\rn_user\Entity
 */
class User extends UserBase implements MappableEntityInterface {

  use MappableEntityTrait;

  /**
   * Gets the latest user customer profile.
   *
   * @return \Drupal\store\Entity\CustomerProfile|null
   */
  public function getCustomerProfile() {
    if (\Drupal::moduleHandler()->moduleExists('store')) {
      $profiles = \Drupal::entityTypeManager()
        ->getStorage('customer_profile')
        ->loadByProperties(['uid.target_id' => $this->id()]);
      ksort($profiles);
      return end($profiles);
    }

    return null;
  }

  /**
   * Get user first name.
   *
   * @return string|null
   */
  public function getFirstName() {
    return !empty($this->get('first_name')) ? $this->get('first_name')->value : null;
  }

  /**
   * Get user last name.
   *
   * @return string|null
   */
  public function getLastName() {
    return !empty($this->get('last_name')) ? $this->get('last_name')->value : null;
  }

  /**
   * Gets user address field.
   *
   * @return \Drupal\address\Plugin\Field\FieldType\AddressItem|null
   */
  public function getAddress() {
    return !empty($this->get('address')) ? $this->get('address')->first() : null;
  }

  /**
   * Get user's phone number
   *
   * @return string|null
   */
  public function getPhoneNumber() {
    return !empty($this->get('phone_number')) ? $this->get('phone_number')->value : null;
  }

  public function getFullName() {
    $firstName = $this->getFirstName();
    $lastName = $this->getLastName();
    if ($firstName && $lastName) {
      $fullName = $firstName . ' ' . $lastName;
    }
    else {
      if ($firstName) {
        $fullName = $firstName;
      }
      elseif ($lastName) {
        $fullName = $lastName;
      }
      else {
        $fullName = t('Valued Customer', [], ['context' => 'User']);
      }
    }
    return $fullName;
  }
}
