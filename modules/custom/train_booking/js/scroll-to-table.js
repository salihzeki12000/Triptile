(function ($) {
  Drupal.behaviors.trainBookingScrollToTable = {
    attach: function (context, settings) {
      $(window).load(function() {
        var tableTop = $('.main-trains').offset().top;
        if(tableTop !== undefined && tableTop > window.scrollY) {
          $('html,body').once('scrollToTop').animate({ scrollTop: tableTop - 10 }, 500);
        }
      });
    }
  };
})(jQuery);