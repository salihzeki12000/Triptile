<?php

namespace Drupal\rn_user;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Password\PhpassHashedPassword as PhpassHashedPasswordBase;

class PhpassHashedPassword extends PhpassHashedPasswordBase {

  /**
   * {@inheritdoc}
   */
  public function check($password, $hash) {
    if (substr($hash, 0, 3) == '#S#') {
      $saltAndHash = substr($hash, 3);
      $hashOffset = strpos($saltAndHash, '#');
      $salt = substr($saltAndHash, 0, $hashOffset);
      $storedHash = substr($saltAndHash, $hashOffset + 1);
      $computedHash = sha1($salt . $password);

      // Compare using hashEquals() instead of === to mitigate timing attacks.
      return $computedHash && Crypt::hashEquals($storedHash, $computedHash);
    }
    else {
      return parent::check($password, $hash);
    }
  }
}