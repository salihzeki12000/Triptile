(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.spinner = {
    attach: function (context, settings) {


      $.each(drupalSettings.spinner, function (index, value) {
        var parameters = JSON.parse(value);
        $('#' + index).spinner(parameters);
        $('#' + index).spinner('value', parameters.defaultValue);
        $('#' + index).after('<div class="spinner-button up"></div>');
        $('#' + index).after('<div class="spinner-button down"></div>');

        $('#' + index).siblings('.spinner-button.up').once('spinnerUpClick').on('click', function(){
          $('#' + index).spinner("stepUp", 1);
        });
        $('#' + index).siblings('.spinner-button.down').once('spinnerDownClick').on('click', function(){
          $('#' + index).spinner("stepDown", 1);
        });
      });



    }
  };
})(jQuery, Drupal, drupalSettings);