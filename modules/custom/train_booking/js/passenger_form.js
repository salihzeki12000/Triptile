(function ($, Drupal) {
  Drupal.behaviors.trainBookingPassengerForm = {
    attach: function (context, settings) {

      // Pushing data to DataLayer after loading the passenger form page.
      $('.train-booking-passenger-form').once('GA DataLayer').each(function () {
        window.trainBooking.checkout(getCheckoutData());
      });

      // Passenger block.
      $('.train-booking-passenger-form .complex-form .passenger-form-header-wrapper').once('PassengerFormHeader').click(function (e) {
        if ($(this).closest('.fields-wrapper').is(':visible')) {
          e.preventDefault();
        }
        else {
          $(this).closest('.route-legs-wrapper').find('.fields-wrapper:visible').hide();
          $(this).siblings('.fields-wrapper').show();
          $('.train-booking-passenger-form .passenger-form-header-wrapper').parent().removeClass('opened');
          $(this).parent().addClass('opened');
        }
      });

      $('.train-booking-passenger-form .save-details-wrapper .button', context).on('mousedown', function (e) {
        $(this).closest('.fields-wrapper').hide();
        $(this).closest('.passenger-wrapper').removeClass('opened');
        if ($('.all-passengers-wrapper.leg-2').length != 0 && $(this).closest('.passenger-wrapper').is(':last-child')) {
          if ($(this).closest('.all-passengers-wrapper.leg-2').length == 0) {
            var $firsPassenger = $('.all-passengers-wrapper.leg-2 .passenger-wrapper.passenger_1');
            $firsPassenger.addClass('opened');
            $firsPassenger.find('.fields-wrapper').show();
          }
        }
        else {
          var $nextPassenger = $(this).closest('.passenger-wrapper').next();
          $nextPassenger.addClass('opened');
          $nextPassenger.find('.fields-wrapper').show();
        }
      });

      $('.train-booking-passenger-form .save-details-wrapper .button', context).on('touchstart', function (e) {
        $('.train-booking-passenger-form .save-details-wrapper .button', context).trigger('mousedown');
      });

      $('.train-booking-passenger-form .provide-later-wrapper input[type="checkbox"]').once('provideLaterChange').change(function () {
        if($(this).prop('checked')) {
          $('.train-booking-passenger-form .passenger-wrapper').hide();
          $('.train-booking-passenger-form .no-show').hide();
          $('.train-booking-passenger-form .provide-later-message').show();
        }
        else {
          $('.train-booking-passenger-form .passenger-wrapper').show();
          $('.train-booking-passenger-form .no-show').show();
          $('.train-booking-passenger-form .provide-later-message').hide();
        }
      });

      // Scroll chosen dob year container to some date.
      if ($('.all-passengers-wrapper.complex-form').length) {
        Drupal.behaviors.chosen.attach(context, settings);
        $('.chosen-container.dob-year').click(function () {
          var $container = $(this).find('.chosen-results');
          var scrollTo = $container.find('[data-option-array-index="64"]');
          $container.animate({
            scrollTop: scrollTo.offset().top - $container.offset().top + $container.scrollTop()
          });
        });
      }

      // Services block
      $('.services-wrapper .service-header').once('ServiceFormHeader').click(function () {
        $(this).closest('.service-wrapper').toggleClass('opened');
      });

      $('.services-wrapper  .button', context).once('ServiceSubmitting').on('mousedown', function () {
        $(this).closest('.service-wrapper').removeClass('opened');
      });

      var $useDetailsCheckboxes = $('.train-booking-passenger-form .simple-form input[type="checkbox"].use-details');
      var useDetailsFromFirstLeg  = '<div class="use-details-from-first-leg">' +
        Drupal.t('Passengers details for second leg will be used from passengers details from the first leg', {}, {'context': 'Timetable Form'}) + '</div>';
      $('#train-booking-passenger-form').once('UseDetails').each(function () {
        if ($useDetailsCheckboxes.is(':checked')) {
          usePassengerDetailsHandler();
        }
      });
      $useDetailsCheckboxes.once('UseDetails').change(function () {
        if($(this).prop('checked')) {
          $useDetailsCheckboxes.each(function() {
            if (!$(this).is(':checked')) {
              $(this).prop('checked', true);
            }
          });
          usePassengerDetailsHandler();
        }
        else {
          $useDetailsCheckboxes.each(function() {
            $(this).prop('checked', false);
            $('.all-passengers-wrapper .passenger-wrapper').each(function () {
              $(this).show();
            });
            $('.all-passengers-wrapper.leg-2 .use-details-from-first-leg').remove();
          });
        }
      });

      // Return prepared data for GA DataLayer.
      function getCheckoutData() {
        var products = [];
        var currencyCode = 'USD';
        $('.leg-info-wrapper').each(function () {
          currencyCode = $(this).attr('data-currency-code');
          var departure = $(this).find('.leg-info-header .stations .departure').text();
          var arrival = $(this).find('.leg-info-header .stations .arrival').text();
          products.push({
            'name': departure + ' - ' + arrival,
            'id': $(this).attr('data-coach-class-id'),
            'price': $(this).attr('data-ga'),
            'category': $(this).attr('data-coach-class-name'),
            'quantity': $(this).attr('data-passengers-count')
          })
        });

        return {
          products: products,
          currencyCode: currencyCode
        }
      }
      
      function usePassengerDetailsHandler() {
        $('.all-passengers-wrapper.leg-2 .passenger-wrapper').each(function () {
          $(this).hide();
        });
        $('.all-passengers-wrapper.leg-2').prepend(useDetailsFromFirstLeg);
      }
    }
  }
})(jQuery, Drupal);
