<?php

namespace Drupal\train_booking\Form;

use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\train_base\Entity\Station;
use Drupal\train_booking\Entity\SuccessSearchDetailed;
use Drupal\train_booking\TrainBookingManager;
use Drupal\train_booking\Entity\FailedSearch;
use Drupal\booking\BookingManagerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\store\Price;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\train_base\Entity\Train;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TimetableForm.
 *
 * @package Drupal\train_booking\Form
 */
class TimetableForm extends TrainBookingBaseForm {

  /**
   * @var \Drupal\store\PriceRule
   */
  protected $priceRule;

  /**
   * TimetableForm constructor.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  public function __construct(ContainerInterface $container) {
    parent::__construct($container);

    $this->priceRule = $container->get('store.price_rule');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'train_booking_timetable_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $session_id = NULL) {
    $form = parent::buildForm($form, $form_state);

    $total_price_from = $total_price_to = NULL;
    $sidebar['stars'] = $sidebar['coach_class'] = [];
    $this->store->setSessionId($session_id);
    $search_request = $this->store->get('search_request');

    // If no result display message and redirect to timetable for existing search request or to home in other case.
    if (empty($search_request)) {
      if ($link = $this->getSearchLink()) {
        drupal_set_message($this->t('Session has been expired. @link', ['@link' => $link], ['context' => 'Session expired']), 'warning');
      }
      else {
        drupal_set_message($this->t('Session has been expired.', [], ['context' => 'Session expired']), 'warning');
      }
      return new RedirectResponse($this->getRedirectUrl());
    }

    $this->trainBookingLogger->logLastStep($this->store->getSessionId(), 2);

    $passengers_number = $search_request['adults'] + $search_request['children'];
    $page_route_name = \Drupal::routeMatch()->getRouteName();
    if ($page_route_name == 'train_booking.timetable_form') {
      $leg = '1';
    }
    elseif ($page_route_name == 'train_booking.timetable_form2') {
      $leg = '2';
    }
    /** @var \Drupal\train_base\Entity\Station $departureStation */
    $departureStation = $this->loadEntity('station', $search_request['legs'][$leg]['departure_station']);
    /** @var \Drupal\train_base\Entity\Station $arrivalStation */
    $arrivalStation = $this->loadEntity('station', $search_request['legs'][$leg]['arrival_station']);
    /** @var \Drupal\Core\Datetime\DrupalDateTime $departureDate */
    $departureDate = $search_request['legs'][$leg]['departure_date'];

    // Update route data if currency codes are different.
    $userCurrency = $this->defaultCurrency->getUserCurrency();
    if ($this->store->get(BookingManagerBase::USER_CURRENCY_KEY) != $userCurrency) {
      $this->store->set(BookingManagerBase::USER_CURRENCY_KEY, $userCurrency);
      $this->updateSearchResult();
    }
    $route_data = $this->store->get('search_result');
    $trains = $route_data[$leg]->getTrains();

    // Prepare data for outputting in route-info.html.twig
    $route_header_info['results_count'] = $this->formatPlural(count($trains), '1 result', '@count results', [], ['context' => 'Timetable Form']);
    $route_header_info['from'] = $departureStation->getName();
    $route_header_info['to'] = $arrivalStation->getName();
    $route_header_info['date'] = $departureDate->format('F d, Y');
    $route_header_info['weekday'] = $departureDate->format('l');
    $route_header_info['save_search'] = $this->getSaveSearchLink($search_request);

    if (empty($trains)) {
      $this->updateFailedSearchStat($departureStation, $arrivalStation, $departureDate);

      $form['no_result'] = [
        '#type' => 'container',
        '#markup' => $this->t('No trains on this route or date.', [], ['context' => 'Timetable Form']),
        '#attributes' => [
          'class' => [
            'no-result',
          ],
        ],
      ];
    }
    else {
      $this->updateSuccessSearchStat($departureStation, $arrivalStation, $departureDate);

      $form['#attached']['library'][] = 'train_booking/timetable-form';
      $form['#attached']['library'][] = 'train_booking/scroll-to-table';
      $form['#attached']['library'][] = 'train_booking/scroll-to-train';
      $form['#attached']['library'][] = 'train_booking/sticky-form';
      $form['#cache'] = ['max-age' => 0];
      $form['#tree'] = TRUE;
      $form['main'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'main-trains'
          ]
        ]
      ];
      if ($leg == '2') {
        if (empty($this->store->get(TrainBookingManager::TIMETABLE_RESULT_KEY)[1])) {
          throw new NotFoundHttpException();
        }
        $form['main']['route_leg_info'] = $this->generateRouteLegInfo('1');
      }
      $form['leg'] = [
        '#type' => 'value',
        '#value' => $leg,
      ];
      $form['main']['route'] = [
        '#theme' => 'route_info',
        '#data' => $route_header_info,
      ];
      $form['main']['trains'] = [
        '#type' => 'container',
      ];
      $form['main']['trains']['no_result'] = [
        '#type' => 'container',
        '#markup' => $this->t('No trains on this route or date.', [], ['context' => 'Timetable Form']),
        '#attributes' => [
          'class' => [
            'no-result',
          ],
        ],
      ];

      $isRoundtripTrainsExist = false;
      $multileg_buffer_time_between_trains = $this->configFactory->get('train_booking.settings')->get('multileg_buffer_time_between_trains') ? : 0;
      /** @var \Drupal\train_provider\TrainInfoHolder $train_info_holder */
      foreach ($trains as $train_key => $train_info_holder) {
        if ($leg == 2) {
          $timetable_result = $this->store->get(TrainBookingManager::TIMETABLE_RESULT_KEY);
          $diff = $train_info_holder->getDepartureDateTime()->getTimestamp() - $timetable_result[1]['arrival_datetime']->getTimestamp();
          if ($diff < $multileg_buffer_time_between_trains) {
            continue;
          }
          $isRoundtripTrainsExist = true;
        }
        /** @var \Drupal\train_base\Entity\Train $train */
        $train = $train_info_holder->getTrain();
        $price_from = $price_to = NULL;
        $arrival_time = $train_info_holder->getDepartureTime() + $train_info_holder->getRunningTime();
        $form['main']['trains']['train_' . $train_key] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'train-wrapper',
            ],
            'data-departure-hours' => floor($train_info_holder->getDepartureTime() / 3600),
            'data-arrival-hours' => floor(($arrival_time % (3600 * 24)) / 3600),
            'data-departure-time' => $train_info_holder->getDepartureTime(),
            'data-travel-time' => $train_info_holder->getRunningTime(),
            'data-arrival-time' => $arrival_time,
            'data-popularity' => $train_info_holder->getCountOfReviews(),
          ],
        ];
        $form['main']['trains']['train_' . $train_key]['key'] = [
          '#type' => 'value',
          '#value' => $train_key,
        ];
        $form['main']['trains']['train_' . $train_key]['coach_classes'] = [
          '#type' => 'container',
          '#weight' => 1,
          '#attributes' => [
            'class' => 'coach-classes-wrapper',
          ],
        ];
        $form['main']['trains']['train_' . $train_key]['coach_classes']['close'] = [
          '#type' => 'container',
          '#markup' => '<div class="close-icon"></div>',
          '#attributes' => [
            'class' => 'close',
          ],
        ];

        /** @var \Drupal\train_provider\CoachClassInfoHolder $coach_class_info_holder */
        foreach ($train_info_holder->getCoachClasses() as $coach_class_key => $coach_class_info_holder) {
          $this->setMinPrice($price_from, $coach_class_info_holder->getPrice());
          $this->setMaxPrice($price_to, $coach_class_info_holder->getPrice());
          $coach_class_code = $coach_class_info_holder->getCoachClass()->getCode();
          $coach_class_name = $coach_class_info_holder->getCoachClass()->getName();
          $coach_class_description = $coach_class_info_holder->getCoachClass()->getDescription();
          $profit = $coach_class_info_holder->getPrice()->subtract($coach_class_info_holder->getOriginalPrice());
          $sidebar_coach_class_code = $this->getCoachClassSidebarCode($coach_class_code);

          $form['main']['trains']['train_' . $train_key]['coach_classes']['coach_class_' . $coach_class_key] = [
            '#type' => 'container',
            '#weight' => 0,
            '#attributes' => [
              'class' => [
                'coach-class-wrapper',
                'visible',
              ],
              'data-coach-class-id' => $coach_class_info_holder->getCoachClass()->id(),
              'data-coach-class-ga' => $profit->getNumber(),
              'data-passengers-count' => $search_request['adults'] + $search_request['children'],
              'coach-class-code' => $coach_class_code,
              'coach-class-price' => $coach_class_info_holder->getPrice()->getNumber(),
              'coach-class-sidebar-code' => $sidebar_coach_class_code,
            ],
          ];
          $form['main']['trains']['train_' . $train_key]['coach_classes']['coach_class_' . $coach_class_key]['radio'] = [
            '#type' => 'radio',
            '#ajax' => [
              'callback' => [$this, 'updateTotalPriceForThisCoachClass'],
              'event' => 'change',
              'progress' => ['type' => 'none'],
            ],
          ];
          $form['main']['trains']['train_' . $train_key]['coach_classes']['coach_class_' . $coach_class_key]['info'] = [
            '#theme' => 'coach_class_info',
            '#data' => $coach_class_info_holder,
          ];
          $form['main']['trains']['train_' . $train_key]['coach_classes']['coach_class_' . $coach_class_key]['key'] = [
            '#type' => 'value',
            '#value' => $coach_class_key,
          ];

          // Sidebar coach class filter.
          $sidebar['coach_class'][$sidebar_coach_class_code]['description'] = $coach_class_description;
          $sidebar['coach_class'][$sidebar_coach_class_code]['name'] = $coach_class_name;
          // Coach class with the same code can be have different price need to calculate min.
          $this->setMinPrice($sidebar['coach_class'][$sidebar_coach_class_code]['price_from'], $coach_class_info_holder->getPrice());
        }

        /** @var \Drupal\store\Price $price_from
         * @var \Drupal\train_base\Entity\Train $train
         */
        $this->setMinPrice($total_price_from, $price_from);
        $this->setMaxPrice($total_price_to, $price_to);
        if ($average_rating = $train_info_holder->getAverageRating()) {
          // Calculate min price for sidebar stars filter.
          $this->setMinPrice($sidebar['stars'][round($average_rating)]['price_from'], $price_from);
          $form['main']['trains']['train_' . $train_key]['#attributes']['data-rate'] = round($average_rating) * 10;
        }
        else {
          $this->setMinPrice($sidebar['stars'][0]['price_from'], $price_from);
          $form['main']['trains']['train_' . $train_key]['#attributes']['data-rate'] = 0;
        }
        $form['main']['trains']['train_' . $train_key]['#attributes']['data-price-from'] = $price_from->getNumber();

        // Gets Supplier Logo
        $supplier = $train_info_holder->getSupplier();
        if (!empty($supplier->getLogo())) {
          $supplier_logo = [
            '#theme' => 'image',
            '#uri' => $supplier->getLogo()->getFileUri(),
            '#alt' => $supplier->getName(),
            '#title' => $supplier->getName(),
          ];
        }
        else {
          $supplier_logo = '';
        }

        // Prepare $train_data array for train-info.html.twig
        $train_data = [
          'train' => $train,
          'departure_station' => $departureStation->id(),
          'arrival_station' => $arrivalStation->id(),
          'departure_time' => $train_info_holder->getDepartureDateTime()->format('H:i'),
          'running_time' => $this->convertSecondsToString($train_info_holder->getRunningTime(), 'running_time'),
          'arrival_time' => $train_info_holder->getArrivalDateTime()->format('H:i'),
          'arrival_note' => $this->arriveNextDayChecker($train_info_holder),
          'supplier_logo' => $supplier_logo,
          'train_number' => $train_info_holder->getTrainNumber(),
          'train_name' => $train_info_holder->getTrainName(),
          'train_class_name' => $train_info_holder->getTrainClass()->getName(),
          'count_of_reviews' => ($train_info_holder->getCountOfReviews() > 0) ? $this->formatPlural($train_info_holder->getCountOfReviews(), '1 review', '@count reviews') : '',
          'passengers_number' => $passengers_number,
          'price_from' => $price_from,
        ];
        if ($train_info_holder->getAverageRating()) {
          $train_data['average_rating'] = $train_info_holder->getAverageRating();
          $train_data['rating_phrase'] = Train::getRatingPhrase($train_info_holder->getAverageRating());
        }
        else {
          $train_data['rating_phrase'] = $this->t('No rating');
        }
        $form['main']['trains']['train_' . $train_key]['train_info'] = [
          '#theme' => 'train_info',
          '#data' => $train_data,
        ];
        $form['main']['trains']['train_' . $train_key]['coach_classes']['actions']['#type'] = 'actions';
        $form['main']['trains']['train_' . $train_key]['coach_classes']['actions']['subtotal-wrapper'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'subtotal-' . $train_key,
              'train-subtotal'
            ],
          ],
        ];
        $form['main']['trains']['train_' . $train_key]['coach_classes']['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Book Tickets', [], ['context' => 'Timetable Form']),
          '#button_type' => 'primary',
          '#weight' => 100,
          '#name' => 'train_' . $train_key,
        ];
        if (!empty($train_info_holder->getMessage())) {
          $form['main']['trains']['train_' . $train_key]['coach_classes']['message'] = [
            '#markup' => '<div class="train-message">' . $train_info_holder->getMessage() . '</div>',
            '#weight' => 100,
          ];
        }
      }

      if ($leg == 2 && !$isRoundtripTrainsExist) {
        $form = [
          '#attributes' => [
            'class' => [
              'train-booking-timetable-form',
            ],
          ],
        ];
        $form['no_result'] = [
          '#type' => 'container',
          '#markup' => $this->t('No trains on this route or date.', [], ['context' => 'Timetable Form Roundtrip Trains don\'t exist.']),
          '#attributes' => [
            'class' => [
              'no-result',
            ],
          ],
        ];
      }
      else {
        // There are filters on sidebar of page.
        $form['sidebar'] = $this->getSidebarFilters($sidebar, $total_price_from, $total_price_to);

        $departure_country = $train_info_holder->getDepartureStation()->getCountry();
        $arrival_country = $train_info_holder->getArrivalStation()->getCountry();
        if ($departure_country && $arrival_country) {
          if ($departure_country == 'RU' && $arrival_country == 'RU') {
            $markup = $this->t('Domestic departures and arrivals are displayed in Moscow time.', [], ['context' => 'Timetable Form']);
          }
          elseif ($departure_country == 'RU' || $arrival_country == 'RU') {
            $markup = $this->t('Russian departures and arrivals are displayed in Moscow time, other in locale time.', [], ['context' => 'Timetable Form']);
          }
          else {
            $markup = $this->t('Domestic departures and arrivals are displayed in locale time.', [], ['context' => 'Timetable Form']);
          }
          $form['sidebar']['time-tip'] = [
            '#type' => 'container',
            '#weight' => -10,
            '#markup' => '<div class="tip">' . $markup . '</div>',
            '#attributes' => [
              'class' => [
                'time-tip',
                'filter-container'
              ],
            ],
          ];
        }
      }
    }

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $submit_values = $form_state->getValues();
    $flag = FALSE;
    foreach ($submit_values['main']['trains'] as $train) {
      unset($train['coach_classes']['actions']);
      foreach ($train['coach_classes'] as $coach_class) {
        if ($coach_class['radio'] == 'on') {
          $flag = TRUE;
        }
      }
    }
    if (!$flag) {
      $form_state->setErrorByName('coach_class', t('You need to pick some coach class', [], ['context' => 'Timetable Form']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submit_name = $form_state->getTriggeringElement()['#name'];
    $values = $form_state->getValues();
    $submit_values = $values['main']['trains'][$submit_name];
    $search_request = $this->store->get('search_request');
    $pax = $search_request['pax'];
    $leg = $values['leg'];
    /** @var \Drupal\train_provider\RouteInfoHolder $routeInfoHolder */
    $routeInfoHolder = $this->store->get('search_result')[$leg];
    if ($routeInfoHolder) {
      /** @var \Drupal\train_provider\TrainInfoHolder $train_info */
      $train_info = $routeInfoHolder->getTrain($submit_values['key']);
    }
    else {
      if ($link = $this->getSearchLink()) {
        drupal_set_message($this->t('Session has been expired. @link', ['@link' => $link], ['context' => 'Session expired']), 'warning');
      }
      else {
        drupal_set_message($this->t('Session has been expired.', [], ['context' => 'Session expired']), 'warning');
      }
      $form_state->setRedirectUrl(Url::fromRoute($this->getHomeRoute()));
      return;
    }
    /** @var \Drupal\train_base\Entity\CoachClass $coach_class */
    unset($submit_values['coach_classes']['actions']);
    foreach ($submit_values['coach_classes'] as $key => $coach_class) {
      if ($coach_class['radio'] == 'on') {
        $coach_class_key = $coach_class['key'];
        break;
      }
    }

    if (isset($coach_class_key)) {
      // @todo use special info holder or Map
      $train_ticket_data = [
        'train_info' => $train_info,
        'coach_class_info' => $train_info->getCoachClass($coach_class_key),
        'departure_datetime' => $train_info->getDepartureDateTime(),
        'running_time' => $this->convertSecondsToString($train_info->getRunningTime()),
        'arrival_datetime' => $train_info->getArrivalDateTime(),
        'pax' => $pax,
      ];

      $user_currency = $this->defaultCurrency->getUserCurrency();
      if ($this->store->get(BookingManagerBase::USER_CURRENCY_KEY) != $user_currency) {
        $this->store->set(BookingManagerBase::USER_CURRENCY_KEY, $user_currency);
      }

      if ($this->isComplexTrip() && $leg == '1') {
        $url = Url::fromRoute('train_booking.timetable_form2', ['session_id' => $this->store->getSessionId()]);
      }
      else {
        $url = Url::fromRoute('train_booking.passenger_form', ['session_id' => $this->store->getSessionId()]);
      }

      $timetable_result = $this->store->get(TrainBookingManager::TIMETABLE_RESULT_KEY);
      $timetable_result = is_null($timetable_result) ? [] : $timetable_result;
      $timetable_result[$leg] = $train_ticket_data;
      $this->trainBookingLogger->logTimetableForm($this->store->getSessionId(), $timetable_result, $this->store->get('search_result'));
      $this->store->set(TrainBookingManager::TIMETABLE_RESULT_KEY, $timetable_result);
      $this->deletePassengerStoreData();
      $form_state->setRedirectUrl($url);
    }
  }

  public function updateTotalPriceForThisCoachClass(&$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $search_request = $this->store->get('search_request');
    $passengersNumber = $search_request['adults'] + $search_request['children'];
    $leg = $form_state->getValue('leg');
    /** @var \Drupal\train_provider\RouteInfoHolder $routeInfoHolder */
    $routeInfoHolder = $this->store->get('search_result')[$leg];
    foreach ($form_state->getValues()['main']['trains'] as $train) {
      $content = '';
      foreach ($train['coach_classes'] as $coach_class) {
        if (!empty($coach_class['radio'])) {
          /** @var \Drupal\train_provider\TrainInfoHolder $train_info_holder */
          $train_info_holder = $routeInfoHolder->getTrain($train['key']);
          $coachClassInfoHolder = $train_info_holder->getCoachClass($coach_class['key']);
          $capacity = $coachClassInfoHolder->getSeatType()->getCapacity();
          $adult_price = $this->priceRule->updatePrice('ticket', $coachClassInfoHolder->getPrice(), ['age' => 30, 'supplier' => $train_info_holder->getSupplier()->getCode()]);
          if ($capacity == 1) {
            /** @var \Drupal\store\Price $total_price */
            $total_price = $adult_price['price']->multiply($search_request['adults']);
            if ($search_request['children'] > 0) {
              foreach ($search_request['children_age'] as $age) {
                $children_price = $this->priceRule->updatePrice('ticket', $coachClassInfoHolder->getPrice(), ['age' => $age, 'supplier' => $train_info_holder->getSupplier()->getCode()]);
                $total_price = $total_price->add($children_price['price']);
              }
            }
            $content = $this->formatPlural($passengersNumber, 'Total price for 1 ticket:', 'Total price for @count tickets:') . ' ' . $total_price;
          }
          else {
            $cabinsCount = ceil($passengersNumber / $capacity);
            $total_price = $adult_price['price']->multiply($cabinsCount);
            $content = $this->formatPlural($cabinsCount, 'Total price for 1 cabin:', 'Total price for @count cabins:') . ' ' . $total_price;
          }

          if ($coachClassInfoHolder->getProduct() && $coachClassInfoHolder->getProduct()->getDescription()) {
            $content .= '<div class="product-note">' . $coachClassInfoHolder->getProduct()->getDescription() . '</div>';
          }
        }
      }
      if ($content) {
        $pickedCoachClassSelector = '.subtotal-' . $train['key'];
        $pickedCoachClassContent = $content;
      }
    }
    $response->addCommand(new RemoveCommand('.train-subtotal div'));
    $response->addCommand(new RemoveCommand('.coach-classes-wrapper > .overlay'));
    if (!empty($pickedCoachClassSelector) && !empty($pickedCoachClassContent)) {
      $response->addCommand(new HtmlCommand($pickedCoachClassSelector, $pickedCoachClassContent));
    }

    return $response;
  }

  protected function deletePassengerStoreData() {
    $this->store->delete(BookingManagerBase::ORDER_KEY);
    $this->store->delete(BookingManagerBase::ORDER_ITEMS_KEY);
    $this->store->delete(TrainBookingManager::PASSENGERS_KEY);
    $this->store->delete(TrainBookingManager::SERVICES_KEY);
    $this->store->delete(BookingManagerBase::INVOICE_KEY);
  }

  /**
   * Generates sidebar filters form, part of timetable form.
   *
   * @param array $sidebar
   * @param \Drupal\store\Price $price_from
   * @param \Drupal\store\Price $price_to
   * @return mixed
   */
  protected function getSidebarFilters(array $sidebar, Price $price_from, Price $price_to) {
    $form = [
      '#tree' => FALSE,
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'sidebar',
          'filters'
        ]
      ]
    ];

    $form['stars'] = [
      '#type' => 'container',
      '#markup' => '<div class="title">' . $this->t('stars:', [], ['context' => 'Timetable Form']) . '</div>',
      '#attributes' => [
        'class' => [
          'stars',
          'filter-container'
        ],
      ],
    ];

    krsort($sidebar['stars']);

    if (count($sidebar['stars']) > 1) {
      $form['stars'][] = $this->generateCheckerUnchecker();
    }

    foreach ($sidebar['stars'] as $rating => $price) {
      $rating_title = $this->getSidebarRatingTitle($price, (int) $rating);
      $form['stars']['rating-' . $rating] = array(
        '#type' => 'checkbox',
        '#title' => $rating_title,
        '#default_value' => TRUE,
        '#attributes' => [
          'value' => $rating * 10,
        ],
      );
    }

    $form['coach_class'] = [
      '#type' => 'container',
      '#markup' => '<div class="title">' . $this->t('car type:', [], ['context' => 'Timetable Form']) .'</div>',
      '#attributes' => [
        'class' => [
          'car-type',
          'filter-container'
        ],
      ],
    ];

    $max_visible_items = 3;
    $items_count = 0;

    $sidebar['coach_class'] = $this->reorderDataByPrice($sidebar['coach_class']);

    if (count($sidebar['coach_class']) > 1) {
      $form['coach_class'][] = $this->generateCheckerUnchecker();
    }

    foreach ($sidebar['coach_class'] as $code => $data) {
      $classes = 'filter-item car-type-item ';
      $classes .= ($items_count < $max_visible_items) ? 'main-items' : 'additional-items';
      if (($items_count > 0) && ($items_count == count($sidebar['coach_class']) - 1)) {
        $classes .= ' last';
      }
      $items_count++;
      if ($items_count > $max_visible_items) {
        $classes .= ' less-mode hide';
      }
      $coach_class_title = $this->getSidebarCoachClassTitle($data, $code);
      $form['coach_class']['coach-class-' . $code] = array(
        '#type' => 'checkbox',
        '#title' => $coach_class_title,
        '#prefix' => '<div class="' . $classes . '">',
        '#suffix' => '</div>',
        '#default_value' => TRUE,
        '#attributes' => [
          'value' => $code,
        ],
      );
    }

    if(count($sidebar['coach_class']) > $max_visible_items) {
      $form['coach_class']['help_buttons'] = [
        '#type' => 'container',
        '#markup' => '<div class="more">' . $this->t('more car type', [], ['context' => 'Timetable Form']) . '</div><div class="less">'
          . $this->t('less car type', [], ['context' => 'Timetable Form']) . '</div>',
        '#attributes' => [
          'class' => [
            'more-less',
          ],
        ],
      ];
    }
    else {
      $form['coach_class']['#attributes']['class'][] = 'no-help-buttons';
    }

    if($price_from !== $price_to) {
      $form['price'] = [
        '#type' => 'container',
        '#markup' => '<div class="title">' . $this->t('price:', [], ['context' => 'Timetable Form']) . '</div>',
        '#attributes' => [
          'class' => [
            'price',
            'filter-container'
          ],
        ],
      ];

      $form['price']['lowest_price'] = [
        '#type' => 'container',
        '#markup' => $price_from,
        '#attributes' => [
          'class' => [
            'lowest',
          ],
        ],
      ];

      $form['price']['highest_price'] = [
        '#type' => 'container',
        '#markup' => $price_to,
        '#attributes' => [
          'class' => [
            'highest',
          ],
        ],
      ];

      $form['price']['price_slider'] = [
        '#type' => 'container',
        '#attributes' => [
          'price-from' => $price_from->getNumber(),
          'price-to' => $price_to->getNumber(),
          'class' => [
            'slider',
            'price-filter'
          ],
        ],
      ];
    }

    $form['departure'] = [
      '#type' => 'container',
      '#markup' => '<div class="title">' . $this->t('departure:', [], ['context' => 'Timetable Form']) . '</div>',
      '#attributes' => [
        'class' => [
          'departure',
          'filter-container'
        ],
      ],
    ];

    $form['arrival'] = [
      '#type' => 'container',
      '#markup' => '<div class="title">' . $this->t('arrival:', [], ['context' => 'Timetable Form']) . '</div>',
      '#attributes' => [
        'class' => [
          'arrival',
          'filter-container'
        ],
      ],
    ];

    $form['departure']['lowest_departure'] = $form['arrival']['lowest_arrival'] = [
      '#type' => 'container',
      '#markup' => '0:00',
      '#attributes' => [
        'class' => [
          'lowest',
        ],
      ],
    ];

    $form['departure']['highest_departure'] = $form['arrival']['highest_arrival'] = [
      '#type' => 'container',
      '#markup' => '24:00',
      '#attributes' => [
        'class' => [
          'highest',
        ],
      ],
    ];

    $form['departure']['departure_slider'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'slider',
          'departure-filter'
        ],
      ],
    ];

    $form['arrival']['arrival_slider'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'slider',
          'arrival-filter'
        ],
      ],
    ];

    $form['reset'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'reset-filters-wrapper',
          'reset-trigger',
          'filter-container',
        ],
      ],
    ];

    $form['reset']['reset_text'] = [
      '#markup' => $this->t('Reset filters', [], ['context' => 'Timetable Form']),
    ];

    return $form;
  }

  protected function getSaveSearchLink($search_request) {
    $link = [
      '#title' => $this->t('Save search'),
      '#type' => 'link',
      '#url' => Url::fromRoute('train_booking.save_search', [], ['query' => $this->getParametersFromSearchRequest($search_request)]),
      '#options' => [
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => '{"width":600}'
        ]
      ],
      '#attached' => ['library' => [
        'core/drupal.dialog.ajax',
        'train_booking/close-modal-dialog'
      ]],
    ];

    return $link;
  }

  /**
   * Gets sidebar coach class title
   *
   * @param array $data
   * @param string $code
   * @return string
   */
  protected function getSidebarCoachClassTitle(array $data, string $code) {
    $coach_class_title = '<span id="class-content-' . $code . '" class="coach-class-filter-item">' . $this->t($data['name']) . '</span>';
    $coach_class_title .= '<div class="lowest-price">' . $data['price_from'] . '</div>';

    return $coach_class_title;
  }

  /**
   * Gets sidebar rating title
   *
   * @param array $price
   * @param int $rating
   * @return string
   */
  protected function getSidebarRatingTitle(array $price, int $rating) {
    if ($rating > 0) {
      $rating_title = '<div class="rating-stars">';
      $rating_title .= '<div class="active-stars"></div></div>';
    }
    else {
      $rating_title = '<div class="rating-stars no-rating">';
      $rating_title .= $this->t('No rating') . '</div>';
    }
    $rating_title .= '<div class="lowest-price">' . $price['price_from'] . '</div>';

    return $rating_title;
  }

  /**
   * @param $min
   * @param \Drupal\store\Price $price_from
   */
  protected function setMinPrice(&$min, Price $price_from) {
    if (empty($min) || $min->greaterThan($price_from)) {
      $min = $price_from;
    }
  }

  /**
   * @param $max
   * @param \Drupal\store\Price $price_to
   */
  protected function setMaxPrice(&$max, Price $price_to) {
    if (empty($max) || $max->lessThan($price_to)) {
      $max = $price_to;
    }
  }

  protected function reorderDataByPrice($data, $order = 'asc') {
    foreach ($data as $code => $info) {
      $temp[$info['price_from']->getNumber()][$code] = $info;
    }
    switch ($order) {
      case 'asc':
        ksort($temp);
        break;
      case 'desc':
        krsort($temp);
        break;
      default:
        ksort($temp);
    }
    foreach ($temp as $items) {
      foreach ($items as $code => $item) {
        $output[$code] = $item;
      }
    }

    return $output;
  }

  /**
   * Updates failed search statistic.
   * @param \Drupal\train_base\Entity\Station $departureStation
   * @param \Drupal\train_base\Entity\Station $arrivalStation
   * @param \Drupal\Core\Datetime\DrupalDateTime $departureDate
   */
  protected function updateFailedSearchStat(Station $departureStation, Station $arrivalStation, DrupalDateTime $departureDate) {
    try {
      if (!$this->store->get('failed_search_stat_updated')) {
        $departure_station_timezone = $departureDate->getTimezone();
        $today = DrupalDateTime::createFromtimestamp(time());
        $today->setTimeZone($departure_station_timezone);
        $today->setTime(0, 0);
        $order_depth = $departureDate->diff($today)->days;
        $this->store->set('failed_search_stat_updated', true);
        $query = $this->entityQuery->get('failed_search')
          ->condition('departure_station', $departureStation->id())
          ->condition('arrival_station', $arrivalStation->id())
          ->condition('departure_date', $departureDate->format(DATETIME_DATE_STORAGE_FORMAT))
          ->condition('order_depth', $order_depth);
        $entity_ids = $query->execute();
        if (!empty($entity_ids)) {
          /** @var \Drupal\train_booking\Entity\FailedSearch $failed_search */
          $failed_search = $this->loadEntity('failed_search', array_pop($entity_ids));
          $failed_search->incrementCount();
        }
        else {
          $failed_search = FailedSearch::create([
            'departure_station' => $departureStation,
            'arrival_station' => $arrivalStation,
            'departure_date' => $departureDate->format(DATETIME_DATE_STORAGE_FORMAT),
            'order_depth' => $order_depth ,
            'count' => 1,
          ]);
        }
        $failed_search->save();
      }
    }
    catch (\Exception $e) {
      // Avoid from any errors because of stat
    }
  }

  /**
   * Updates success search statistic.
   * @param \Drupal\train_base\Entity\Station $departureStation
   * @param \Drupal\train_base\Entity\Station $arrivalStation
   * @param \Drupal\Core\Datetime\DrupalDateTime $departureDate
   */
  protected function updateSuccessSearchStat(Station $departureStation, Station $arrivalStation, DrupalDateTime $departureDate) {
    try {
      if (!$this->store->get('success_search_detailed_id')) {
        $departure_station_timezone = $departureDate->getTimezone();
        $today = DrupalDateTime::createFromtimestamp(time(), ['timezone' => $departure_station_timezone]);
        $today->setTime(0, 0);
        $depth = $departureDate->diff($today)->days;
        $today = DrupalDateTime::createFromtimestamp(time());

        $entity_ids = $this->entityQuery->get('success_search_detailed')
          ->condition('departure_station', $departureStation->id())
          ->condition('arrival_station', $arrivalStation->id())
          ->condition('date_of_search', $today->format(DATETIME_DATE_STORAGE_FORMAT))
          ->condition('depth', $depth)
          ->execute();
        if (!empty($entity_ids)) {
          /** @var \Drupal\train_booking\Entity\SuccessSearchDetailed $successSearch */
          $successSearch = $this->loadEntity('success_search_detailed', reset($entity_ids));
          $successSearch->incrementCount();
        }
        else {
          $successSearch = SuccessSearchDetailed::create([
            'departure_station' => $departureStation,
            'arrival_station' => $arrivalStation,
            'date_of_search' => $today->format(DATETIME_DATE_STORAGE_FORMAT),
            'depth' => $depth ,
            'count' => 1,
          ]);
        }
        $this->isComplexTrip() ? $successSearch->incrementComplexTripCount() : $successSearch->incrementOneWayTripCount();
        $successSearch->save();

        $this->store->set('success_search_detailed_id', $successSearch->id());
      }
    }
    catch (\Exception $e) {
      // Avoid from any errors because of stat
    }
  }

  /**
   * @param \Drupal\train_provider\TrainInfoHolder $train_info_holder
   * @return string
   */
  protected function arriveNextDayChecker($train_info_holder) {
    $depDate = new \Datetime($train_info_holder->getDepartureDateTime()->format('Y-m-d'));
    $arrDate = new \Datetime($train_info_holder->getArrivalDateTime()->format('Y-m-d'));
    $days = date_diff($depDate, $arrDate, true)->format('%a');
    if ($days > 0) {
      if ($days == 1) {
        return $this->t('Arrival next day');
      }
      else {
        return $this->t('Arrival on @date',  ['@date' => $train_info_holder->getArrivalDateTime()->format("d F")]);
      }
    }

    return null;
  }

  /**
   * @param int $seconds
   * @param string $type
   * @return string
   */
   protected function convertSecondsToString(int $seconds = 0, $type = 'basic') {
    switch ($type) {
      case 'basic':
        $h = (int)floor($seconds / 3600);
        $m = (int)floor(($seconds % 3600) / 60);
        $s = (int)floor(($seconds % 3600) % 60);
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
      case 'departure_time':
        $h = (int)floor($seconds / 3600);
        $m = (int)floor(($seconds % 3600) / 60);
        return sprintf('%02d:%02d', $h, $m);
      case 'running_time':
        $h = (int)floor($seconds / 3600);
        $m = (int)floor(($seconds % 3600) / 60);
        return sprintf('%02dh%02d', $h, $m);
      case 'arrival_time':
        $h = (int)floor(($seconds % (3600 * 24)) / 3600);
        $m = (int)floor((($seconds % (3600 * 24)) % 3600) / 60);
        return sprintf('%02d:%02d', $h, $m);
    }
  }

  protected function generateCheckerUnchecker() {
    $check_uncheck_container['check_uncheck'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'check-uncheck-wrapper',
        ],
      ],
    ];

    $check_uncheck_container['check_uncheck']['checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Uncheck all', [], ['context' => 'Timetable Form']),
      '#default_value' => TRUE,
      '#attributes' => [
        'class' => [
          'checker',
          'check-uncheck-item',
        ],
      ],
    ];

    return $check_uncheck_container;

  }

  /**
   * Gets coach class code for sidebar
   *
   * @param $coach_class_code
   * @return string
   */
  protected function getCoachClassSidebarCode($coach_class_code) {
    foreach ($this->getDuplicatedCoachClassCodes() as $duplicated_code) {
      if (preg_match("/^" . $duplicated_code . "/", $coach_class_code)) {
        return $duplicated_code;
      }
    }
    return $coach_class_code;
  }

  /**
   * Gets array of coach class code parts
   *
   * @return array
   */
  protected function getDuplicatedCoachClassCodes() {
    return ['1Р_', '1P_'];
  }
}
