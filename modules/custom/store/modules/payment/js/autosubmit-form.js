(function ($) {
  Drupal.behaviors.paymentAutosubmitForm = {
    attach: function (context, settings) {
      if ($('form.payment-autosubmit').length) {
        setTimeout(function(){$('form.payment-autosubmit').submit();}, 3000);
      }
    }
  };
})(jQuery);
