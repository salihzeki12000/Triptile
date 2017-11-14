<?php

namespace Drupal\store\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SwitchCurrency.
 *
 * @package Drupal\store\Controller
 */
class SwitchCurrency extends ControllerBase {

  /**
   * User currency cookie.
   */
  const USER_CURRENCY_COOKIE = 'user_currency';

  /**
   * Switchcurrency.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function switchCurrency(Request $request) {
    // Get new currency.
    $currency = \Drupal::routeMatch()->getParameter('currency');
    $visible_currencies = \Drupal::config('store.settings')->get('visible_currencies');

    // Return response.
    $destination = $request->query->get('destination');
    $response = $destination ? new RedirectResponse($destination) : $this->redirect($this->getHomeRoute());
    if (isset($visible_currencies[$currency]) && $visible_currencies[$currency]) {

      // Update user currency cookie if new currency is different.
      if ($currency != $request->cookies->get(self::USER_CURRENCY_COOKIE)) {
        $response->headers->setCookie(new Cookie(self::USER_CURRENCY_COOKIE, $currency));
      }
    }
    return $response;
  }

  protected function getHomeRoute() {
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $url = Url::fromRoute('<front>', [], ['language' => $language]);
    $route = $url->getRouteName();

    return $route;
  }

}
