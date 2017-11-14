(function ($, Drupal) {
  Drupal.behaviors.trainBookingAutoFocus = {
    attach: function (context, settings) {

      var maxWidth = 768;

      if ($('body').hasClass('front') && $(window).width() >= maxWidth) {
        var $departureSelectize = $('.stations-wrapper .departure-station')[0].selectize;
        var $arrivalSelectize = $('.stations-wrapper .arrival-station')[0].selectize;
        var formMode;
        var leg;
        var currentValue = " ";
        $('.stations-wrapper .departure-station').once('clickDepartureStation').on('change', function (e) {
          var keyCode = window.event.keyCode;
          var $form = $(this).closest('.form-flex-container');
          var hover = $form.is(':hover');
          formMode = $form.attr('form-mode');
          leg = $(this).attr('leg');
          $departureSelectize = $(this)[0].selectize;
          currentValue = $departureSelectize.getValue();
          if (currentValue && currentValue != " " && formMode != 'complex-mode' && leg != 'leg-2' && hover && keyCode != 9) {
            $('.' + formMode + ' .' + leg + ' .stations-wrapper > .form-item-selectize:last-child input').trigger('focus');
          }
        });
        $('.stations-wrapper .arrival-station').once('clickArrivalStation').on('change', function () {
          var $form = $(this).closest('.form-flex-container');
          var hover = $form.is(':hover');
          formMode = $form.attr('form-mode');
          leg = $(this).attr('leg');
          $arrivalSelectize = $(this)[0].selectize;
          currentValue = $arrivalSelectize.getValue();
          if (currentValue && currentValue != " " && formMode != 'complex-mode' && hover) {
            $('.' + formMode + ' .' + leg + '.travel-date-input').trigger('click');
          }
        });

        // Complex form behaviour.
        /*$('.complex-mode .leg-1 .travel-date-wrapper input.departure-date').once('changeDepartureDate').change(function() {
          $('.complex-mode .leg-2 .stations-wrapper .arrival-station input').trigger('focus');
        });*/
      }

    }
  };
})(jQuery, Drupal);