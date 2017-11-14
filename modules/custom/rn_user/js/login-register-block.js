(function ($) {
  Drupal.behaviors.rnUserLoginRegisterBlockBehavior = {
    attach: function (context, settings) {

      $('.login-register-block').find('.mobile-btn.logged-out').once('signInButtonClick').click(function (e) {
        var mobile_max_width = drupalSettings.settings_mobile_width;

        if (mobile_max_width !== undefined) {
          var active_on_open = getActiveTab($(this));
          var block_content = $(this).closest('.login-register-block').find('.block__content');
          var $form = $(this).closest('.login-register-block').find('.block__content');
          var dialogWidth = $(window).width() >= mobile_max_width ? 670 : $(window).width();
          var dialogHeight = $(window).width() >= mobile_max_width ? 'auto' : $(window).height();
          $form.dialog({
            autoOpen: true,
            modal: true,
            width: dialogWidth,
            height: dialogHeight,
            dialogClass: "login-form-popup",
            resizable: false,
            draggable: false,
            open: function (event, ui) {
              $('.tab-name').removeClass('active');
              $('.tab-form').removeClass('active');
              $('.tab-name#' + active_on_open).addClass('active');
              $('.tab-form.' + active_on_open + '-form').addClass('active');
              var $this = $(this);
              if ($this.height() > $(window).height()) {
                $this.find('.popup-form-wrapper').css('height', $(window).height() - $('.ui-dialog-titlebar').outerHeight());
              }
              $this.find('.tab-name').once('tabPopupLoginRegisterClick').click(function (e) {
                var $tab_name = $(this);
                if (!$tab_name.hasClass('active')) {
                  $('.tab-name').removeClass('active');
                  $('.tab-form').removeClass('active');
                  $tab_name.addClass('active');
                  $('.' + $tab_name.attr('id') + '-form').addClass('active');
                }
              });
              $this.find('.user-register-form').perfectScrollbar({theme: 'user-block-theme'});
              $this.find('.forgot-pass-link').once('tabPopupLoginRegisterClick').click(function (e) {
                $this.find('.forgot-pass-form').addClass('active');
                $('.tab-name#sign-up, .tab-form.sign-in-form').removeClass('active');
              });
            },
            close: function (event, ui) {
              $(this).dialog("destroy");
            }
          });
        }
      });

      function getActiveTab($button) {
        var button_id = $button.attr('id');
        if (button_id !== undefined) {
          var pos = button_id.indexOf('-btn');
          if (pos !== false) {
            return button_id.substring(0, pos);
          }
        }
        return 'sign-in';
      }
    }
  };

  Drupal.behaviors.showPassword = {
    attach: function (context, settings) {
      $(context).find(':password').after('<div class="eye"><span></span></div>');
      $(".eye").click(function(){
        if($(this).prev().attr("type") =="password"){
          $(this).toggleClass('eye-show').prev().attr("type","text");
        }else{
          $(this).toggleClass('eye-show').prev().attr("type","password");
        }
      });
    }
  }

})(jQuery);
