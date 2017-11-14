(function ($) {
  Drupal.behaviors.paymentChangeNumber = {
    attach: function (context, settings) {
      var $form = $('.payment-form');
      var $options = $('.payment-method-options');
      var $terms = $('.terms-and-conditions');
      var elements = ['credit-card'];

      $options.once('formRadiosChange').change(function () {
        var selected_option = $(this).find('input[type="radio"]:checked').attr('data');
        if(elements.indexOf(selected_option) > -1) {
          $form.find('fieldset.payment-data.' + selected_option).addClass('number-2');
          $terms.removeClass('number-2');
          $terms.addClass('number-3');
        }
        else {
          $form.find('fieldset.payment-data').removeClass('number-2');
          $terms.removeClass('number-3');
          $terms.addClass('number-2');
        }

      });
    }
  };

})(jQuery);