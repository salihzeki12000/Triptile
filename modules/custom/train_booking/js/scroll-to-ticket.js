(function ($) {
  Drupal.behaviors.trainBookingScrollToTicket = {
    attach: function (context, settings) {
      $(window).load(function() {
         var wrapperTop = $('.leg-info-wrapper').offset().top;
        if(wrapperTop !== undefined && wrapperTop > window.scrollY) {
          $('html,body').once('scrollToTicket').animate({ scrollTop: wrapperTop }, 500);
        }
      });
    }
  };
})(jQuery);