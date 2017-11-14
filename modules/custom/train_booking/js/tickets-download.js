(function ($, Drupal) {
  Drupal.behaviors.trainBookingTicketsDownload = {
    attach: function (context, settings) {
      
      /******************* Track Ticket Download ******************/
      var $agreement =  $('.agreement .form-checkbox');
      var $pdf = $('.pdf-link');

      // Add/Remove class 'disabled' for pdf link.
      if ($agreement.prop('checked')) {
        $pdf.removeClass('disabled');
      }
      $agreement.once('AgreementPolicy').change(function () {
        if ($(this).prop('checked')) {
          $pdf.removeClass('disabled');
        }
        else {
          $pdf.addClass('disabled');
        }
      });

      // Send request to the server.
      $pdf.once('buttonPDFDownload').click(function(e) {
        if ($(this).hasClass('disabled')) {
          e.preventDefault();
        }
        else {
          var orderHash = drupalSettings.orderHash;
          $.ajax({
            url: '/track-ticket-download/' + orderHash,
            dataType: 'json'
          });
        }
      });
    }
  }
})(jQuery, Drupal);