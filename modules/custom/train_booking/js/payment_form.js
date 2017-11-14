(function ($, Drupal) {
  Drupal.behaviors.trainBookingPaymentForm = {
    attach: function (context, settings) {

      // Create cookie for more clear GA statistics.
      $('.train-booking-payment-form').once('GA DataLayer').each(function () {
        var expirationDate = $('#edit-main').attr('data-storage-expiration-date');
        createCookie('ga_need_to_push', 'true', expirationDate);
      });

      function createCookie(name,value,expirationDate) {
        var expires = "";
        if (expirationDate) {
          var date = new Date(expirationDate*1000);
          expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + value + expires + "; path=/";
      }
    }
  }
})(jQuery, Drupal);