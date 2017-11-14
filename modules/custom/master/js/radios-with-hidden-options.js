(function(Drupal, $) {

  Drupal.behaviors.masterRadiosWithHiddenOptions = {
    attach: function (context, settings) {
      $('.radios-hidden').once('radiosWithHiddenOptions').hide();
      $('.toggle-options').once('radiosWithHiddenOptions').click(function(e) {
        var $parent = $(this).parent();
        if ($(this).hasClass('more-options')) {
          $parent.find('.radios-hidden').show();
          $parent.find('.less-options').show();
          $(this).hide();
        }
        else if ($(this).hasClass('less-options')) {
          $parent.find('.radios-hidden').hide();
          $parent.find('.more-options').show();
          $(this).hide();
        }
        e.preventDefault();
      });
    }
  };

})(Drupal, jQuery);
