<?php
/**
 * Created by PhpStorm.
 * User: sergei
 * Date: 9/27/16
 * Time: 12:00 PM
 */

namespace Drupal\rn_user;

use Drupal\user\PrivateTempStore;


class SessionStore extends PrivateTempStore {

  // @todo add method to delete session.

  /**
   * @var string
   */
  protected $session_id;

  /**
   * @var int
   */
  protected $expiration_time;

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $expire = $this->getSessionExpirationTime() - REQUEST_TIME;
    $key = $this->createkey($key);
    if (!$this->lockBackend->acquire($key)) {
      $this->lockBackend->wait($key);
      if (!$this->lockBackend->acquire($key)) {
        throw new SessionStoreException("Couldn't acquire lock to update item '$key' in '{$this->storage->getCollectionName()}' session storage.");
      }
    }

    $value = (object) array(
      'owner' => $this->getOwner(),
      'data' => $value,
      'updated' => (int) $this->requestStack->getMasterRequest()->server->get('REQUEST_TIME'),
    );
    $this->storage->setWithExpire($key, $value, $expire);
    $this->lockBackend->release($key);
  }

  /**
   * {@inheritdoc}
   */
  protected function createkey($key) {
    return parent::createkey($this->getSessionId() . ':' . $key);
  }

  /**
   * Sets unique id used as a part of key in storage.
   *
   * @param string $session_id
   * @return static
   */
  public function setSessionId(string $session_id) {
    $this->session_id = $session_id;
    return $this;
  }

  /**
   * Gets unique id used as a part of key in storage.
   *
   * @return string
   */
  public function getSessionId() {
    if (empty($this->session_id)) {
      $this->setSessionId($this->generateSessionId());
    }
    return $this->session_id;
  }

  /**
   * Generates a random string.
   *
   * @return string
   */
  protected function generateSessionId() {
    $request = $this->requestStack->getCurrentRequest();
    $ip = $request->getClientIp() ? $request->getClientIp() : 'localhost';
    $ua = $request->headers->get('HTTP_USER_AGENT') ? $request->headers->get('HTTP_USER_AGENT') : 'ua';

    // generate new id based on random # / ip / user agent / uniqid
    return md5(mt_rand(0, 999999) . uniqid($ip . $ua, true));
  }

  /**
   * Sets session expiration time.
   *
   * @param int $expiration_time
   * @return static
   * @throws \Drupal\rn_user\SessionStoreException
   */
  public function setSessionExpirationTime(int $expiration_time) {
    if ($this->get('_expiration_time')) {
      throw new SessionStoreException('Session expiration time is already set.');
    }
    $this->expiration_time = $expiration_time;
    $this->set('_expiration_time', $expiration_time);
    return $this;
  }

  /**
   * Gets current session expiration time.
   *
   * @return int
   * @throws \Drupal\rn_user\SessionStoreException
   */
  public function getSessionExpirationTime() {
    if (!$this->expiration_time) {
      $expiration_time = $this->get('_expiration_time');
      if (!$expiration_time) {
        throw new SessionStoreException('Session expiration time is not set.');
      }
      $this->expiration_time = $expiration_time;
    }

    return $this->expiration_time;
  }

  /**
   * Checks if the session exists in the storage.
   *
   * @return bool
   * @throws SessionStoreException
   */
  public function sessionExist() {
    if (!$this->session_id) {
      throw new SessionStoreException('Session Id is not set.');
    }

    try {
      $expiration_time = $this->getSessionExpirationTime();
      return $expiration_time > REQUEST_TIME;
    }
    catch (SessionStoreException $e) {
      return FALSE;
    }
  }

}