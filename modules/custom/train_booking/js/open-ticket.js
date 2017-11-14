(function ($) {
  Drupal.behaviors.trainBookingOpenTicket = {
    attach: function (context, settings) {

      // Open ticket
      $('.ticket-trigger').once('openTicketClick').click(function () {
        $(this).parents('.leg-info-wrapper').toggleClass('opened');
      });
    }
  };
})(jQuery);