(function ($) {
  Drupal.behaviors.trainBookingMoveMultilegPopup = {
    attach: function (context, settings) {
      var $dialog = $('.popup-search-form, .complex-mode');
      var padding = 20;

      $dialog.find('.travel-date-input').once('multiLegTravelDateClick').click(function() {
        moveIfOutOfViewport($(this).siblings('.datepicker-element'));
      });

      $dialog.find('.adult-number-input').once('multiLegAdultClick').click(function() {
        moveIfOutOfViewport($(this).siblings('.form-item-complex-mode-passengers-wrapper-adults'));
      });

      $dialog.find('.children-number-input').once('multiLegChildrenClick').click(function() {
        moveIfOutOfViewport($(this).siblings('.children-wrapper'));
      });

      $dialog.find('.spinner-button').once('spinnerButtonClick').click(function() {
        moveIfOutOfViewport($(this).parents('.children-wrapper'));
      });

      function moveDialog() {
        $dialog.dialog({
          position: {
            my: "top+" + padding,
            at: "top",
            of: window,
            using: function (pos, ext) {
              $(this).animate({ top: pos.top }, 500);
            }
          }
        });
      }

      function getElementButtom($element) {
        return $element.offset().top + $element.height();
      }

      function outOfViewport(bottomPosition) {
        return (bottomPosition > $(window).height() - padding) ? 1 : 0;
      }

      function moveIfOutOfViewport($element) {
        if(outOfViewport(getElementButtom($element)) ) {
          moveDialog();
        }
      }
    }
  };
})(jQuery);