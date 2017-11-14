(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.calendar = {
    attach: function (context, settings) {
      $('.element-calendar').datepicker();
    }
  };
})(jQuery, Drupal, drupalSettings);