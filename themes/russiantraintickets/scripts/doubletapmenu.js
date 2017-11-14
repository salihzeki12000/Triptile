(function ($) {
    Drupal.behaviors.doubletapmenu = {
        attach: function (context, settings) {
            // for menu on tablets and mobile devices
            if ($(window).width() > 736) {
                $( '.menu-name--main.menu-level-1' ).doubleTapToGo();
            }
        }
    };
}(jQuery));
