(function ($) {
  Drupal.behaviors.trainBookingScrollForm = {
    attach: function (context, settings) {
      var maxWidth = 768;
      if ($(window).width() >= maxWidth) {
        var formClass = 'scrolled';
        var $form = $('.train-booking-search-form');
        var bottomLimit = 400;
        var topOffset = 230;

        $('#edit-departure-station-selectized').once('departureInputFocus').on('focus', function() {
          scrollPage($(this));
        });

        $('#edit-arrival-station-selectized').once('arrivalInputFocus').on('focus', function() {
          scrollPage($(this));
        });

        $('.travel-date-wrapper').once('travelDateClick').on('click', function() {
          scrollPage($(this));
        });

        $('.passengers-field').once('passengersNumberClick').on('click', function() {
          scrollPage($(this));
        });


        $(window).scroll(function() {
          if($form.hasClass(formClass)) {
            $form.removeClass(formClass);
          }
        })

        function scrollPage($element) {
          if(!$form.hasClass(formClass) && needToScroll() && !$element.closest(".complex-mode").length) {
            $('html,body').animate({ scrollTop: topOffset }, 500);
            $form.addClass(formClass);
          }
        }

        function needToScroll() {
          var element_bottom = $form.offset().top + $form.height();
          return ($(window).height() - element_bottom > bottomLimit)  ? false : true;
        }
      }
    }
  };
})(jQuery);