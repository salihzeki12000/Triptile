<?php

namespace Drupal\rn_user;

use Drupal\user\PrivateTempStoreFactory;

/**
 * Class PrivateTempStoreFactory.
 *
 * @package Drupal\rn_user
 */
class SessionStoreFactory extends PrivateTempStoreFactory {

  /**
   * {@inheritdoc}
   */
  function get($collection) {
    // Store the data for this collection in the database.
    $storage = $this->storageFactory->get("rn_user.session_store.$collection");
    return new SessionStore($storage, $this->lockBackend, $this->currentUser, $this->requestStack, $this->expire);
  }

}
