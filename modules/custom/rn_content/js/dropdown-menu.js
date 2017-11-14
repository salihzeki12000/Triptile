(function ($) {
  Drupal.behaviors.rnDropdownMenuBehavior = {
    attach: function (context, settings) {
      $('.dropdown-trigger').once('openMobileMenu').click(function() {
        $('.dropdown-menu').dialog({
          title: Drupal.t("Menu", {}, {'context': 'RN Content'}),
          dialogClass: "popup-menu",
          height: $(window).height(),
          width: $(window).width(),
          draggable: false
        });
      });
      $('.dropdown-menu').bind(
        'dialogclose',
        function(event) {
          if ($(this).data("ui-dialog")) {
            $(this).dialog("destroy");
          }
        }
      );
    }
  };

})(jQuery);