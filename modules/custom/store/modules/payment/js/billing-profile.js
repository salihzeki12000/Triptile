(function ($) {
  Drupal.behaviors.billingProfile = {
    attach: function (context, settings) {
      var billing = [
        'form-item-credit-card-billing-profile-address-line1',
        'form-item-credit-card-billing-profile-postal-code',
        'form-item-credit-card-billing-profile-locality',
        'form-item-credit-card-billing-profile-administrative-area'
      ];
      var $country = $('#edit-credit-card-billing-profile-country-code');
      if ($country[0].selectize) {
        $.each(billing, function (i, element) {
          $('.' + element).hide();
        });
      }
      $country.once('selectCountry').on('change', function () {
        if ($country[0].selectize) {
          var selectize = $country[0].selectize;
          var countryCode = selectize.getValue();
          if (countryCode == 'US') {
            $.each(billing, function (i, element) {
              $('.' + element).show();
            });
          }
          else {
            $.each(billing, function (i, element) {
              $('.' + element).hide();
            });
          }
        }
      });
    }
  };

})(jQuery);