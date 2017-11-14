(function ($) {
    Drupal.behaviors.trainBookingCloseModalDialog = {
        attach: function (context, settings) {
            $('body').on("click", ".ui-widget-overlay", function () {
                $('#' + $(".ui-dialog").attr('aria-describedby')).dialog('destroy');
            });
        }
    };
})(jQuery);