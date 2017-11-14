(function ($) {
  Drupal.behaviors.trainBookingScrollToTrain = {
    attach: function (context, settings) {

      $('.select-seat').once('selectSeatButtonClick').click(function(){
        var offset = $(this).parents('.train-wrapper').offset();
        if(offset !== undefined) {
          $('html,body').animate({ scrollTop: offset.top }, 500);
        }
      });
    }
  };
})(jQuery);