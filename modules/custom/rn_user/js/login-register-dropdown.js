(function ($) {
  Drupal.behaviors.rnUserLoginRegisterDropdownBehavior = {
    attach: function (context, settings) {
      var mobile_max_width = drupalSettings.settings_mobile_width;
      if (mobile_max_width !== undefined) {
        if ($(window).width() > mobile_max_width) {
          var block_class = '.login-register-block';
          var container_class = '.dropdown-container';
          var toggle_class = 'opened';
          $(block_class).find('.login-register-dropdown-trigger').once('loginRegisterDropdownTriggerClick').click(function () {
            $(this).siblings(container_class).toggleClass(toggle_class);
          });

          // Hide block on click outside the popup
          $('html').once('loginRegisterDropdownHtmlClick').click(function (e) {
            var target = $(e.target);
            if (!target.closest(block_class).length) {
              $(block_class).find(container_class).removeClass(toggle_class);
            }
          });

          // Hide block on Sign In/Sign out link click
          $('.logged-out').once('loggedOutLinkClick').click(function() {
            $(block_class).find(container_class).removeClass(toggle_class);
          });
        }
      }
    }
  };

})(jQuery);