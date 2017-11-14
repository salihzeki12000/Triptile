(function ($) {
  Drupal.behaviors.trainBookingScrollToPaymentForm = {
    attach: function (context, settings) {
      $(window).load(function() {
        var paymentForm = $('#edit-payment-wrapper').offset().top;
        if(paymentForm !== undefined && paymentForm > window.scrollY) {
          $('html,body').once('scrollToTop').animate({ scrollTop: paymentForm }, 500);
        }
      });
    }
  };
})(jQuery);