(function ($, Drupal, settings) {

  Drupal.behaviors.ttScript = {
    attach: function (context, settings) {

      $(".mobile-menu").once('ttScriptBehavior').on('click', function(){
        $("#block-tt-main-menu").toggle();
        $(".mobile-mask").toggle();
      });

      $(".mobile-mask").once('ttScriptBehavior').on('click', function(){
        $("#block-tt-main-menu").hide();
        $(".mobile-mask").hide();
      });
      $('.mobile-settings').once('ttScriptBehavior').on('click', function(){
        if($(this).hasClass('open')){
          $('.mobile-settings-wrapper').hide();
          $(this).removeClass('open');
        }
        else{
          $('.mobile-settings-wrapper').show();
          $(this).addClass('open');
        }
      });


      /*
       * Hide tips when user click on close or mask.
       * Save to cookie, not to show tips when refresh page.
       */
      $('.trip-tips-close, .popup-mask').once('ttScriptBehavior').on('click', function () {
        $('.trip-tips').hide();
        $('.popup-mask').hide();
        $.cookie('show_tips', 0);
      });

      /*
       * Open, close for settings menu
       */
      $('#block-toprightmenu').once('ttScriptBehavior').on('click', function () {
        $('.choose-settings').toggle();
        if($('.choose-settings').is(':visible')){
          $('#block-toprightmenu').addClass('open');
        }
        else{
          $('#block-toprightmenu').removeClass('open');
        }
      });

      $(document).once('ttScriptBehavior').mouseup(function(e){
        var container = $(".choose-settings");
        // if the target of the click isn't the container nor a descendant of the container
        if (!container.is(e.target) && container.has(e.target).length === 0 && !$(e.target).hasClass('open-settings')){
          container.hide();
          $('#block-toprightmenu').removeClass('open');
        }
      });

    }
  };


})(jQuery, Drupal, drupalSettings);